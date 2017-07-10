<?php
namespace Front\Model;

use Think\Page;

class FinanceModel
{

    public function getFinanceData($type = 0)
    {
        $per = C('DB_PREFIX');
        $site = intval($_GET['site']);
        switch ($site) {
            case 2:
                $map = ' d.repayment_time=0 AND d.deadline<' . time() . ' AND d.status in(3,5,7) ';
                $field = "sum(d.capital)+sum(d.interest) as capital_all,d.borrow_uid,m.user_name,d.borrow_id,b.borrow_name,sum(d.interest) as interest,d.repayment_time,d.deadline";
                
                // 分页处理
                import("ORG.Util.Page");
                $xcount = M('investor_detail d')->field("d.id")
                    ->where($map)
                    ->group('d.sort_order,d.borrow_id')
                    ->buildSql();
                $newxsql = M()->query("select count(*) as tc from {$xcount} as t");
                $count = $newxsql[0]['tc'];
                $p = new Page($count, 10);
                $page = $p->show();
                $Lsql = "{$p->firstRow},{$p->listRows}";
                // 分页处理
                
                $lately = M('investor_detail d')->field($field)
                    ->join("{$per}members m ON m.id=d.borrow_uid")
                    ->join("{$per}borrow_info b ON b.id=d.borrow_id")
                    ->where($map)
                    ->group('d.sort_order,d.borrow_id')
                    ->order("d.deadline asc")
                    ->limit($Lsql)
                    ->select();
                
                $this->assign("page", $page);
                $this->assign("tendlately", $lately);
                
                $data['html'] = $this->fetch('finanz_res1');
                exit(json_encode($data));
                break;
            case 3:
                $map = ' b.status in(4,5) AND b.investor_uid <> 1';
                $field = " b.investor_uid,sum(investor_capital) as money_all,b.investor_uid,m.user_name ";
                
                // 分页处理
                import("ORG.Util.Page");
                $xcount = M('borrow_investor b')->field("b.investor_uid")
                    ->where($map)
                    ->group('b.investor_uid')
                    ->buildSql();
                $newxsql = M()->query("select count(*) as tc from {$xcount} as t");
                $count = $newxsql[0]['tc'];
                $p = new Page($count, 10);
                $page = $p->show();
                $Lsql = "{$p->firstRow},{$p->listRows}";
                // 分页处理
                
                $lately = M('borrow_investor b')->field($field)
                    ->join("{$per}members m ON m.id=b.investor_uid")
                    ->where($map)
                    ->group('b.investor_uid')
                    ->order("money_all desc")
                    ->limit($Lsql)
                    ->select();
                
                foreach ($lately as $k => $v) {
                    $lately[$k]['money_all'] = Fmoney($v['money_all']);
                }
                
                $this->assign("page", $page);
                $this->assign("tenddata", $lately);
                $data['html'] = $this->fetch('finanz_res2');
                exit(json_encode($data));
                break;
            case 4:
                $map = ' b.status in(4,5) AND b.borrow_uid <> 1';
                $field = " b.borrow_uid,sum(investor_capital) as money_all,b.borrow_uid,m.user_name ";
                // 分页处理
                import("ORG.Util.Page");
                $xcount = M('borrow_investor b')->field("b.investor_uid")
                    ->where($map)
                    ->group('b.investor_uid')
                    ->buildSql();
                $newxsql = M()->query("select count(*) as tc from {$xcount} as t");
                $count = $newxsql[0]['tc'];
                $p = new Page($count, 10);
                $page = $p->show();
                $Lsql = "{$p->firstRow},{$p->listRows}";
                // 分页处理
                
                $lately = M('borrow_investor b')->field($field)
                    ->join("{$per}members m ON m.id=b.borrow_uid")
                    ->where($map)
                    ->group('b.borrow_uid')
                    ->order("money_all desc")
                    ->limit($Lsql)
                    ->select();
                
                foreach ($lately as $k => $v) {
                    $lately[$k]['money_all'] = Fmoney($v['money_all']);
                }
                
                $this->assign("page", $page);
                $this->assign("tenddata", $lately);
                
                $data['html'] = $this->fetch('finanz_res2');
                exit(json_encode($data));
                break;
            case 1:
            default:
                $time = strtotime(date("Y-m-d"));
                $map = ' d.deadline> ' . $time . ' AND d.deadline< ' . ($time + 3600 * 24 * 7) . ' AND d.status in(1,2,7) ';
                $field = "sum(d.capital)+sum(d.interest) as capital_all,d.borrow_uid,m.user_name,d.borrow_id,b.borrow_name,sum(d.interest) as interest,d.repayment_time,d.deadline";
                
                // 分页处理
                import("ORG.Util.Page");
                $xcount = M('investor_detail d')->field("d.id")
                    ->where($map)
                    ->group('d.sort_order,d.borrow_id')
                    ->buildSql();
                $newxsql = M()->query("select count(*) as tc from {$xcount} as t");
                $count = $newxsql[0]['tc'];
                $p = new Page($count, 10);
                $page = $p->show();
                $Lsql = "{$p->firstRow},{$p->listRows}";
                // 分页处理
                
                $lately = M('investor_detail d')->field($field)
                    ->join("{$per}members m ON m.id=d.borrow_uid")
                    ->join("{$per}borrow_info b ON b.id=d.borrow_id")
                    ->where($map)
                    ->group('d.sort_order,d.borrow_id')
                    ->order("d.deadline asc")
                    ->limit($Lsql)
                    ->select();
                
                $this->assign("page", $page);
                $this->assign("tendlately", $lately);
                
                $data['html'] = $this->fetch('finanz_res1');
                exit(json_encode($data));
                break;
        }
    }
}