<?php
namespace Member\Model;

class MenuModel
{

    public static function init()
    {
        return [
            '个人中心' => [
                'icon' => '&#xe611',
                'maps' => [
                    'message',
                    'member',
                    'password',
                    'verify',
                    'index'
                ],
                'list' => [
                    '我的消息' => [
                        'url' => U('/Member/Message/index'),
                        'maps' => [
                            '/member/message/index'
                        ],
                        'list' => [
                            '平台消息' => [
                                'url' => U('/Member/Message/lists'),
                                'maps' => [
                                    '/member/message/lists'
                                ]
                            ]
                        ]
                    ],
                    '基本资料' => [
                        'url' => U('/Member/Member/index'),
                        'maps' => [
                            '/member/member/index'
                        ],
                        'list' => [
                            /* '头像管理' => [
                                'url' => U('/Member/Member/header'],
                                'maps' => [
                                    '/member/member/header'
                                )
                            ], */
                            '个人资料' => [
                                'url' => U('/Member/Member/info')
                            ],
                            '联系方式' => [
                                'url' => U('/Member/Member/contact')
                            ],
                            '单位资料' => [
                                'url' => U('/Member/Member/career')
                            ],
                           /*  '财务状况' => [
                                'url' => U('/Member/Member/finance')
                            ],
                            '房产资料' => [
                                'url' => U('/Member/Member/house')
                            ],
                            '联保资料' => [
                                'url' => U('/Member/Member/assure')
                            ) */
                        ]
                    ],
                    '密码管理' => [
                        'url' => U('/Member/Password'),
                        'maps' => [
                            '/member/password/index'
                        ],
                        'list' => [
                            '密码管理' => [
                                'url' => U('/Member/Password')
                            ],
                            '支付密码' => [
                                'url' => U('/Member/Password/Pay')
                            ]
                        ]
                    ]
                    ,
                    '认证中心' => [
                        'url' => U('/Member/Verify/index'),
                        'maps'=>[
                            '/member/verify/index'
                        ],
                        'list' => [
                            '手机认证' => [
                                'url' => U('/Member/Verify/mobile')
                            ],
                            '邮箱认证' => [
                                'url' => U('/Member/Verify/email')
                            ],
                            '实名认证' => [
                                'url' => U('/Member/Verify/Id')
                            ]
                            /* '资料认证' => [
                                'url' => U('/Member/Verify/Info')
                            ) */
                        ]
                    ]
                    
                ]
            ],
            '投资管理' => [
                'icon' => '&#xe67c;',
                'maps' => [
                    'invest',
                    'auto'
                ],
                'list' => [
                    '投资列表' => [
                        'url' => U('/Member/Invest/index'),
                        'maps' => [
                            '/member/invest/index'
                        ],
                        'list' => [
                            '竞标中投标' => [
                                'url' => U('/Member/Invest/doing')
                            ],
                            '回收中投标' => [
                                'url' => U('/Member/Invest/recoveryIn')
                            ],
                            '逾期投资' => [
                                'url' => U('/Member/Invest/overdue')
                            ],
                            '已回收投资' => [
                                'url' => U('/Member/Invest/recovery')
                            ],
                            '投资统计' => [
                                'url' => U('/Member/Invest/statistics')
                            ]
                        ]
                    ],
                    '自动投标' => [
                        'url' => U('/Member/Auto/index'),
                        'maps' => [
                            '/member/auto/index'
                        ],
                        'list' => [
                            '参数设置' => [
                                'url' => U('/Member/Auto/setting')
                            ],
                            '设置详情' => [
                                'url' => U('/Member/Auto/save')
                            ]
                        ]
                    ]
                ]
            ],
            '借款管理' => [
                'icon' => '&#xe62d;',
                'maps' => [
                    'borrow',
                    'quota'
                ],
                'list' => [
                    '借款统计' => [
                        'url' => U('/Member/Borrow/index'),
                        'maps' => [
                            '/member/borrow/index'
                        ],
                        'list' => [
                            '发标中借款' => [
                                'url' => U('/Member/Borrow/doIng')
                            ],
                            '偿还中借款' => [
                                'url' => U('/Member/Borrow/repayIn')
                            ],
                            '逾期中借款' => [
                                'url' => U('/Member/Borrow/overdue')
                            ],
                            '失败的借款' => [
                                'url' => U('/Member/Borrow/fail')
                            ],
                            '已还清借款' => [
                                'url' => U('/Member/Borrow/payOff')
                            ],
                            '借款统计' => [
                                'url' => U('/Member/Borrow/statistics')
                            ],
                        ]
                    ],
                    '申请额度' => [
                        'url' => U('/Member/Quota/index'),
                        'maps' => [
                            '/member/quota/index'
                        ],
                        'list' => [
                            '申请额度' => [
                                'url' => U('/Member/Quota/index')
                            ],
                            '申请记录' => [
                                'url' => U('/Member/Quota/log')
                            ]
                        ]
                   ]
                ]
            ],
            '个人资金' => [
                'icon' => '&#xe794;',
                'maps' => [
                    'capital',
                    'bank',
                    'withdraw',
                    'charge'
                ],
                'list' => [
                    '资金统计' => [
                        'url' => U('/Member/Capital/index'),
                        'maps' => [
                            '/member/capital/index'
                        ],
                        'list' => [
                            '资金详细' => [
                                'url' => U('/Member/Capital/index')
                            ],
                            '变动详细' => [
                                'url' => U('/Member/Capital/log')
                            ]
                        ]
                    ],
                    '银行账户' => [
                        'url' => U('/Member/Bank/index'),
                        'maps' => [
                            '/member/bank/index'
                        ],
                        'list' => [
                            '银行账户' => [
                                'url' => U('/Member/Bank/index')
                            ]
                        ]
                    ],
                    '提现管理' => [
                        'url' => U('/Member/WithDraw/index'),
                        'maps' => [
                            '/member/withdraw/index'
                        ],
                        'list' => [
                            '申请提现' => [
                                'url' => U('/Member/WithDraw/apply')
                            ],
                            '提现记录' => [
                                'url' => U('/Member/WithDraw/applyLog')
                            ]
                        ]
                    ],
                    '充值管理' => [
                        'url' => U('/Member/Charge/index'),
                        'maps' => [
                            '/member/charge/index'
                        ],
                        'list' => [
                            '在线充值' => [
                                'url' => U('/Member/Charge/onLine')
                            ],
                            '线下充值' => [
                                'url' => U('/Member/Charge/offLine')
                            ],
                            '充值记录' => [
                                'url' => U('/Member/Charge/log')
                            ]
                        ]
                    ]
                ]
            ],
            '活动中心' => [
                'icon' => '&#xe60a;',
                'maps' => [
                    'invite'
                ],
                'list' => [
                    '有奖邀请' => [
                        'url' => U('/Member/Invite/index'),
                        'maps' => [
                            '/member/invite/index'
                        ],
                        'list' => [
                            '邀请好友' => [
                                'url' => U('/Member/Invite/friends')
                            ],
                            '奖金记录' => [
                                'url' => U('/Member/Invite/rewardLog')
                            ],
                            '推广记录记录' => [
                                'url' => U('/Member/Invite/popularize')
                            ]
                        ]
                    ]
                ]
            ]
        ];
    }

    /**
     *
     * @param string $node
     * @return array
     * @author 周阳阳 2017年4月19日 上午11:01:18
     */
    public static function getChildren($node = '')
    {
        $menu = [];
        $data = self::init();
        foreach ($data as $k => $v) {
            if (in_array($node, $v['maps'])) {
                $key = array_keys($v['maps'], $node);
                $_temp = current(array_slice($v['list'], $key[0], 1));
                
                return $_temp['list'];
            } else {
                continue;
            }
        }
    }
}