<?php
/**
 * Created by IntelliJ IDEA.
 * User: fankys
 * Date: 2019/2/15
 * Time: 15:38
 */

namespace frame\swoole\server;


class HttpServer extends Server
{
    public function onRequest($request, $response)
    {
        $uri = $request->server['request_uri'];
        $file = APP_DIR . $uri;
        $pathinfo = pathinfo($file, PATHINFO_EXTENSION);
        print_r($uri);
        print_r($pathinfo);
        if ($uri == '/' or $uri == $this->index or empty($pathinfo)) {
            echo "frame file";
            $this->bootstrap->onRequest($request, $response);
            //无指定扩展名
        } elseif ($uri != '/' and $pathinfo != 'php' and is_file($file)) {
            // 非php文件, 最好使用nginx来输出
//            $response->header('Content-Type', FileHelper::getMimeTypeByExtension($file));
            echo "static file";
            $response->sendfile($file);
        } elseif ($uri != '/' && $uri != $this->index) {
            //站点目录下的其他PHP文件
            echo "php file";
            $this->handleDynamic($file, $request, $response);
        }
    }

    /**
     * 处理动态请求
     * @param SwooleRequest $request
     * @param SwooleResponse $response
     */
    protected function handleDynamic($file, SwooleRequest $request, SwooleResponse $response)
    {
        if (is_file($file)) {
            $response->header('Content-Type', 'text/html');
            ob_start();
            try {
                include $file;
                $response->end(ob_get_contents());
            } catch (\Exception $e) {
                $response->status(500);
                $msg = $e->getMessage();
                $response->end($msg);
            }
            ob_end_clean();
        } else {
            $response->status(404);
            $response->end("Page Not Found({$request->server['request_uri']})！");
        }
    }

    public function onTask(SwooleServer $serv, int $task_id, int $src_worker_id, $data)
    {
//        $result = $this->bootstrap->onTask($serv, $task_id, $src_worker_id, $data);
//        return $result;
    }

    public function onFinish(SwooleServer $serv, int $task_id, string $data)
    {
//        $this->bootstrap->onFinish($serv, $task_id, $data);
    }
}