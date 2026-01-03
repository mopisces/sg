<?php
return [
    'sg_user0'     => 'karry/karry',
    'sg_user1'     => 'good/good',
    'sg_user2'     => 'ok/ok',
    'factory_name' => '江西瑞森--本地测试',
    'db_config'    => [
        [
            'DB_FLAG'            => '协和隆3100', //数据库标识（生产线名称）
            'DB_TYPE'            => 'sqlsrv', //数据库类型（支持2005,2008）
            'DB_HOST'            => 'LEAPER-PC\MWJ', //服务器地址
            'DB_USER'            => 'sa', //用户名
            'DB_PWD'             => 'karry', //密码
            'DB_NAME'            => 'cpmsdbv4.5', //数据库名
            'DB_DATA'            => 1, //是否从数据库读取数据              
            'isnew'              => 1, //老生管0 新生管1
            'updown'             => 0, //单刀0 上下刀1
            'paperCodeNumber'    => 1, //纸质代码占几个字符
            'paperCodeSpaceChar' => '-',
            'socketio'   => [
                'port'   => 50007,//这个端口号好像可以随意配置（不同线不一样）
                'timer'  => 1,//php中间层接受udp的时间间隔（单位：秒，决定了前端页面显示的数据每隔多少秒刷新1次）
                //'domain' => 'http://jxrs.leaper.ltd:42000',//'http://test.leaper.ltd:8888',
                "domain"=> "http://lpkj.leaper.ltd:50007"
            ],
            'socket_bind' => [
                'address' => '192.168.0.19',//安装php环境的电脑内网ip
                'port'    => 2000,//“飞机”配置的端口号 有可能要+1（不同线不一样）
                'flag'    => 'DATA1234',//标识符
            ]
        ],
        [
            'DB_FLAG'            => '江苏炫彩BHS', //数据库标识（生产线名称）
            'DB_TYPE'            => 'sqlsrv', //数据库类型（支持2005,2008）
            'DB_HOST'            => '.\MWJ', //服务器地址
            'DB_USER'            => 'sa', //用户名
            'DB_PWD'             => 'karry', //密码
            'DB_NAME'            => 'cimdbv4', //数据库名
            'DB_DATA'            => 0, //是否从数据库读取数据    
            'isnew'              => 1, //老生管0 新生管1
            'updown'             => 0, //单刀0 上下刀1 
            'paperCodeNumber'    => 1, //纸质代码占几个字符
            'paperCodeSpaceChar' => '-',
            'socketio'   => [
                'port'   => 60000,//这个端口号好像可以随意配置（不同线不一样）
                'timer'  => 1,//php中间层接受udp的时间间隔（单位：秒，决定了前端页面显示的数据每隔多少秒刷新1次）
                'domain' => 'http://lpkj.leaper.ltd:50008'//'http://test.leaper.ltd:8888',
            ],
            'socket_bind' => [
                'address' => '192.168.0.19',//'192.168.1.195',//安装php环境的电脑内网ip
                'port'    => 3000,//5001,//“飞机”配置的端口号 有可能要+1（不同线不一样）
                'flag'    => '',//标识符DATA1234 || Line1:
            ]
        ]
    ]
];