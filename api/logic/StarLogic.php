<?php
/**
 * Created by IntelliJ IDEA.
 * User: fankys
 * Date: 2018/12/18
 * Time: 11:46
 */

namespace api\logic;


use frame\Base;

class StarLogic
{
    /**
     * 添加 明星关键词
     * @param array $params
     */
    public function getAddFeilds(&$params)
    {
        $original_id = $params['original_id'];
        $source      = isset($params['source'])?:'cms';
        if(empty($original_id)) {
            return ['ret'=>1,'reason'=>'original_id can not empty',
                'data'=>['msg_id'=>$params['msg_id']?:'']];
        }
        $_id = md5($original_id.$source);
        $starKeywords = [
            '_id'          => $_id,
            'name'         => $params['name'],
            'category'     => ['star'],
            'weight'       => isset($params['weight']) ? (int)$params['weight'] : 0,//权重
            't_click'      => isset($params['t_click']) ? (int)$params['t_click'] : 0,//总点击数
            'state'        => isset($params['state']) ? (int)$params['state'] : 0,//状态 1：启用 0：禁用
            'oned_click'   => isset($params['oned_click']) ? (int)$params['oned_click'] : 0,//1日点击量
            'sd_click'     => isset($params['sd_click']) ? (int)$params['sd_click'] : 0,//7日点击量
            'sd_avg_click' => isset($params['sd_avg_click']) ? (int)$params['sd_avg_click'] : 0,//7日日均点击量
            'fth_click'    => isset($params['fth_click']) ? (int)$params['fth_click'] : 0,//15日点击量
            'fth_agv_click'=> isset($params['fth_agv_click']) ? (int)$params['fth_agv_click'] : 0,//15日日均点击量
            'm_click'      => isset($params['m_click']) ? (int)$params['m_click'] : 0,//30日点击量
            'm_agv_click'  => isset($params['m_agv_click']) ? (int)$params['m_agv_click'] : 0,//30日日均点击量
            'create_time'  => Base::$curr_date_time, //创建时间
            'modify_time'  => Base::$curr_date_time,//修改时间
            'original_id'  => $original_id,
            'source'       => $source,
            'cites_counter'=> 0,
            'msg_id'       => (isset($params['msg_id']) && $params['msg_id']) ? $params['msg_id']: "",
        ];
        return ['ret'=>0,'data'=>$starKeywords];
    }

    /**
     * 更新字段
     * @param $params
     * @return array
     */
    public function getEditFields($params)
    {
        $data = [
            'name'=>$params['name'],
        ];
        $int_fields = [
            'weight',
            't_click',
            'state',
            'oned_click',
            'sd_click',
            'sd_avg_click',
            'fth_click',
            'fth_agv_click',
            'm_click',
            'm_agv_click',
        ];
        foreach ($int_fields as $key) {
            if(isset($params[$key])) {
                $data[$key] = (int)$params[$key];
            }
        }
        return $data;
    }

}