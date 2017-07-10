<?php
namespace Backstage\Model;

use Think\Model;

class InvestModel extends Model
{
    protected $tableName = 'investor_detail';
    
    public function getInvestorList($where = array(),$field = '*',$page_now = 1,$order='id desc',$page_size = 0)
    {
        
    }
    /**
     * 
     * @example
     * 
     * @param array $where
     * @param string $field
     * @author 周阳阳 2017年5月2日 下午4:22:41
     */
    public function getInvestorListForChart()
    {
        $week_times = array(
            strtotime(date('Y-m-d', time())),
            strtotime('+1 weeks')
        );
        $week_times_serialize = array(
            date('m月-d日',time()),
            date('m月-d日',strtotime('+1 day')),
            date('m月-d日',strtotime('+2 day')),
            date('m月-d日',strtotime('+3 day')),
            date('m月-d日',strtotime('+4 day')),
            date('m月-d日',strtotime('+5 day')),
            date('m月-d日',strtotime('+6 day')),  
        );
        $where = array(
            'b.status'=>6    
        );
        $field = '';
        $rows = $this->table($this->tablePrefix . 'borrow_investor as b')
                ->join($this->tablePrefix.'investor_detail as i on b.borrow_id = i.borrow_id','left')
                ->field($field)
                ->where($where)
                ->select();

        $json = array(
            'title'=>array(
                'text'=>'最近一周等待还款统计',
                'x'=>-20,
                
            ),
            'subtitle'=>array(
                'text'=>'数据来源:统计中心',
                'x'=>-20,
            
            ),
            'xAxis'=>[
                'categories'=>$week_times_serialize
                
            ],
            'yAxis' => array(
                'title' => '金额（元）',
                'plotLines' => [
                    [
                        'value' => 0,
                        'width' => 1,
                        'color' => '#808080'
                    ]
                ]
            ),
            'tooltip'=>[
                'valueSuffix'=> '元'
            ],
            'legend'=>[
                'layout'=> 'vertical',
                'align'=> 'right',
                'verticalAlign'=> 'middle',
                'borderWidth'=> 0
        ],
        'series'=>[
            [
                'name'=>'待还金额',
                'data'=>[7.0, 6.9, 9.5, 14.5, 18.2, 21.5, 25.2]
                
            ]
          ]
        );

        return json_encode($json);
/*         var chart = new Highcharts.Chart('container', {
            title: {
                text: '不同城市的月平均气温',
                x: -20
            },
            subtitle: {
                text: '数据来源: WorldClimate.com',
                x: -20
            },
            xAxis: {
                categories: ['一月', '二月', '三月', '四月', '五月', '六月', '七月', '八月', '九月', '十月', '十一月', '十二月']
            },
            yAxis: {
                title: {
                    text: '温度 (°C)'
                },
                plotLines: [{
                    value: 0,
                    width: 1,
                    color: '#808080'
                }]
            },
            tooltip: {
                valueSuffix: '°C'
            },
            legend: {
                layout: 'vertical',
                align: 'right',
                verticalAlign: 'middle',
                borderWidth: 0
            },
            series: [{
                name: '东京',
                data: [7.0, 6.9, 9.5, 14.5, 18.2, 21.5, 25.2, 26.5, 23.3, 18.3, 13.9, 9.6]
            }, {
                name: '纽约',
                data: [-0.2, 0.8, 5.7, 11.3, 17.0, 22.0, 24.8, 24.1, 20.1, 14.1, 8.6, 2.5]
                }, {
                    name: '柏林',
                    data: [-0.9, 0.6, 3.5, 8.4, 13.5, 17.0, 18.6, 17.9, 14.3, 9.0, 3.9, 1.0]
                }, {
                    name: '伦敦',
                    data: [3.9, 4.2, 5.7, 8.5, 11.9, 15.2, 17.0, 16.6, 14.2, 10.3, 6.6, 4.8]
                    }]
        }); */
            
    }
}