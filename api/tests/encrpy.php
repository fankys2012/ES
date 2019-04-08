<?php
/**
 * Created by IntelliJ IDEA.
 * User: fankys
 * Date: 2019/4/8
 * Time: 15:06
 */

namespace tests;


class encrpy
{
    //请求参数的加密方式
    private static $requestEncryptType = null;
    //请求加密模式，只针对AES
    private static $requestEncryptMode = null;
    //密钥组
    private static $requestEncryptKeyGroup = null;

    //响应数据的加密方式
    private static $responseEncryptType = null;
    //响应数据的加密模式，只针对AES
    private static $responseEncryptMode = null;
    //密钥组
    private static $responseEncryptKeyGroup = null;

    //解密出来的请求参数
    private static $requestParams = array();

    //AES加密模式
    private static $encryptMode = array(
        "0" => MCRYPT_MODE_ECB,
        "1" => MCRYPT_MODE_CBC,
    );
    //AES加密算法名称
    private static $cipher = array(
        "128" => MCRYPT_RIJNDAEL_128,
        "256" => MCRYPT_RIJNDAEL_256,
    );

    /**
     * 解密请求数据
     * @param $codec
     * @param $pack
     * @return bool
     */
    public static function decryptRequest($codec,$pack)
    {
        if(strlen($codec) <8 || empty($pack))
        {
            return false;
        }
        self::getDecryptParam($codec);
        return self::decryptSolution($pack);

    }

    /**
     * 对响应数据加密
     * @param $codec
     * @param $data
     */
    public static function encryptResponse($codec,$data)
    {
        if(strlen($codec) <8 || empty($pack))
        {
            return false;
        }
        self::getEncryptParam($codec);
        return self::decryptSolution($data);

    }

    /**
     * 获取解密基本参数信息
     * @param string $codec
     */
    private static function getDecryptParam($codec)
    {
        //第1位 加密方式 1为rsa+sha1签名  2为rsa+aes加密
        self::$requestEncryptType = substr(0,1,$codec);
        //第2位加密模式 加密模式只针对与AES加密(0 ecb 1 cbc),如果是rsa+sha1方案，此位置应该为0
        self::$requestEncryptMode = substr(1,1,$codec);
        //第3-4位 使用的秘钥组
        self::$requestEncryptKeyGroup = intval(substr(2,2,$codec));
    }

    /**
     * 获取加密基本参数信息
     * @param string $codec
     */
    private static function getEncryptParam($codec)
    {
        //第5位 加密方式 1为rsa+sha1签名  2为rsa+aes加密
        self::$responseEncryptType = substr(4,1,$codec);
        //第6位加密模式 加密模式只针对与AES加密(0 ecb 1 cbc),如果是rsa+sha1方案，此位置应该为0
        self::$responseEncryptMode = substr(5,1,$codec);
        //第7-8位 使用的秘钥组
        self::$responseEncryptKeyGroup = intval(substr(6,2,$codec));
    }

    /**
     * 数据加密
     * @param $data
     */
    private static function encryptSolution($data)
    {
        $packData = '';
        //秘钥组
        $keyInfo = self::getKeysInfo(self::$responseEncryptKeyGroup,'server');

        if(self::$responseEncryptType == 0)
        {
            return $data;
        }
        elseif (self::$responseEncryptType == 1)
        {
            //先对结果进行签名
            $priv_key_id = openssl_pkey_get_private(base64_decode($keyInfo['private_key']));
            openssl_sign($data, $signature, $priv_key_id, "sha1WithRSAEncryption");

            $sig_len = strlen($signature);

            //组合成rsa+sha1签名格式的数据
            $result = pack('CSa*a*', 0, $sig_len, $signature, $data);
            $packData = base64_encode($result);
        }
        elseif (self::$responseEncryptType == 2)
        {
            //先用rsa加密aes参数
            $aes_params = array(
                "mode" => self::$responseEncryptMode,
                "bits" => 128,
                "init" => self::generateRandomData(),
                "pass" => self::generateRandomData(),
            );
            $str_aes_param = json_encode($aes_params);
            $private_key = openssl_pkey_get_private(base64_decode($keyInfo['private_key']));

            $encrypt_aes_param = nn_crypt_api::rsa_private_encrypt($str_aes_param, $private_key);
            $encrypt_aes_param_len = strlen($encrypt_aes_param);

            //通过aes参数进行数据加密
            $aes_key = pack('H*', $aes_params['pass']);
            $encrypt_data = self::aesEncrypt($data, self::$responseEncryptMode, $aes_params['bits'], $aes_key, $aes_params['init']);

            //组合成rsa+aes格式的数据
            $result = pack('CSa*a*', 0, $encrypt_aes_param_len, $encrypt_aes_param, $encrypt_data);
            $packData = base64_encode($result);

        }
        return $packData;
    }

    /**
     * 解密打包pack 数据
     * @param string $pack 打包信息
     */
    private static function decryptSolution($pack)
    {
        //密码组
        $keyInfo = self::getKeysInfo(self::$requestEncryptKeyGroup,'server');

        if(self::$requestEncryptType == 0) //没加密
        {
           return;
        }
        elseif (self::$requestEncryptType == 1) // rsa+sha1
        {
            $unpack = self::unpackRsaSha1Data($pack);
            if($unpack == false)
            {
                return false;
            }
            //秘钥校验
            $verify = self::verifySign($unpack['sig'],$unpack['data'],$keyInfo['public_key']);
            if($verify == false)
            {
                return false;
            }
            self::$requestParams = json_decode($unpack['data'],true);
            return true;
        }
        elseif (self::$requestEncryptType == 2) //rsa+aes加密方案
        {
            $unpack = self::unpackRsaAesData($pack);
            $publicKey = openssl_pkey_get_public(base64_decode($keyInfo['public_key']));

            openssl_public_decrypt($unpack['aes_param'], $aesParams, $publicKey);

            $arr_aes_param = json_decode($aesParams, true);

            $aes_key = pack('H*', $arr_aes_param['pass']);

            $decrypt_data = self::aesDecrypt($unpack['data'], $arr_aes_param['mode'], $arr_aes_param['bits'], $aes_key, $arr_aes_param['init']);

            //获取解密出来的请求参数
            $decrypt_data = str_replace("\0", "", $decrypt_data);
            self::$requestParams = json_decode($decrypt_data, true);
        }
    }

    /**
     * 解包rsa+sha1方式打包的数据
     * @param $pack
     * @return  array
     */
    private static function unpackRsaSha1Data($pack)
    {
        $packData = self::base64urlDecode($pack);
        //规则一定要约定好，不然没法unpack
        $info = unpack("Chdr/Ssig_len/a*data", $packData);
        if (!is_array($info) || $info['sig_len'] < 1)
        {
            return false;
        }

        $data['len'] = $info['sig_len'];//秘钥长度
        $data['sig'] = substr($info['data'], 0, $info['sig_len']);//从数据中取出秘钥
        $data['data'] = substr($info['data'], $info['sig_len']);//剩余部分则是打包的数据
        return $data;
    }

    /**
     * 解包rsa+aes方式打包的数据
     * @param $pack
     * @return bool
     */
    private static function unpackRsaAesData($pack)
    {
        $packData = self::base64urlDecode($pack);
        $info = unpack("Chdr/Saes_len/a*data", $packData);
        if (!is_array($info) || $info['aes_len'] < 1)
        {
            return false;
        }
        $data['len'] = $info['aes_len'];
        $data['aes_param'] = substr($info['data'], 0, $info['aes_len']);
        $data['data'] = substr($info['data'], $info['aes_len']);
        return $data;
    }

    /**
     * @param $data 字符串
     * @return string 生成url安全的base64字符串
     */
    public static function base64urlEncode($data)
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * @param $data url安全的base64字符串
     * @return string 转会base64原样的字符串
     */
    public static function base64urlDecode($data)
    {
        return base64_decode(str_pad(strtr($data, '-_', '+/'), strlen($data) % 4, '=', STR_PAD_RIGHT));
    }

    /**
     * openssl_verify 验证
     * @param string $sign  秘钥
     * @param string $data  加密数据
     * @param string $signKey 解密秘钥
     * @return bool
     */
    private static function verifySign($sign,$data,$signKey)
    {
        $public_key_id = openssl_pkey_get_public((base64_decode($signKey)));
        if ($public_key_id)
        {
            $verify = openssl_verify($data, $sign, $public_key_id, OPENSSL_ALGO_SHA1);
            openssl_free_key($public_key_id);
            if ($verify == 1)
            {
                return true;
            }
            else
            {
                return false;
            }
        }
        return true;
    }

    /**
     * @param $data 加密数据
     * @param $cipher
     * @param $bits 加密位数
     * @param $pass 加密密码
     * @param $iv   初始值
     * @internal param 加密模式 $mode
     * @return string 返回解密之后的数据
     * 使用AES解密
     */
    private static function aesDecrypt($data, $cipher, $bits, $pass, $iv)
    {
        $_mode = self::$encryptMode[$cipher];
        $_cipher = self::$cipher[$bits];
        if ($_mode == MCRYPT_MODE_ECB)
        {
            $decrypt_data = mcrypt_decrypt($_cipher, $pass, $data, $_mode);
        }
        else
        {
            $decrypt_data = mcrypt_decrypt($_cipher, $pass, $data, $_mode, $iv);
        }

        return $decrypt_data;
    }

    static public function generateRandomData()
    {
        // 密码字符集，可任意添加你需要的字符
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';

        $password = '';
        $length = 16;

        for ($i = 0; $i < $length; $i++)
        {
            $password .= $chars[mt_rand(0, strlen($chars) - 1)];
        }
        //生成16个字节的数据
        $result = bin2hex(md5($password, true));
        return $result;
    }

    /**
     * @param $data 需要被加密的数据
     * @param $cipher 算法名称序号
     * @param $bits 加密数据位数
     * @param $pass 加密密码
     * @param $iv 初始化
     * @internal param 加密模式 $mode
     * @return string 返回加密数据
     */
    private static function aesEncrypt($data, $cipher, $bits, $pass, $iv)
    {
        $_mode = self::$encryptMode[$cipher];
        $_cipher = self::$cipher[$bits];
        if ($_mode == MCRYPT_MODE_ECB)
        {
            $encrypt_data = mcrypt_encrypt($_cipher, $pass, $data, $_mode);
        }
        else
        {
            $encrypt_data = mcrypt_encrypt($_cipher, $pass, $data, $_mode, $iv);
        }

        return $encrypt_data;
    }

    /**
     * 获取秘钥组
     * @param $index
     * @param $type
     */
    public static function getKeysInfo($index,$type)
    {
        if($type == 'client')
        {
            $keys = encryptKeys::$rsa_client_keys;
        }
        else
        {
            $keys = encryptKeys::$rsa_server_keys;
        }
        return $keys[$index];
    }
}