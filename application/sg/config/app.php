<?php
return [
    'sg_user0'     => 'karry/karry',
    'sg_user1'     => 'good/good',
    'sg_user2'     => 'ok/ok',
    'factory_name' => '测试',
    'db_config'    => [
        [
            'DB_FLAG'            => '新郑永盛', //数据库标识（生产线名称）
            'DB_TYPE'            => 'sqlsrv', //数据库类型（支持2005,2008）
            'DB_HOST'            => '.\MWJ', //服务器地址
            'DB_USER'            => 'sa', //用户名
            'DB_PWD'             => 'karry', //密码
            'DB_NAME'            => 'cpmsdbfh', //数据库名
            'isnew'              => 1, //老生管0 新生管1
            'updown'             => 0, //单刀0 上下刀1
            'paperCodeNumber'    => 1, //纸质代码占几个字符
            'paperCodeSpaceChar' => '-',
            'socketio'   => [
                'port'   => 8888,//这个端口号好像可以随意配置（不同线不一样）
                'timer'  => 1,//php中间层接受udp的时间间隔（单位：秒，决定了前端页面显示的数据每隔多少秒刷新1次）
                'domain' => 'http://sclx.leaper.ltd:8888',//'http://test.leaper.ltd:8888',
            ],
            'socket_bind' => [
                'address' => '192.168.0.230',//安装php环境的电脑内网ip
                'port'    => 5946,//“飞机”配置的端口号 有可能要+1（不同线不一样）
                'flag'    => 'DATA1234',//标识符
            ]
        ],
        [
            'DB_FLAG'            => '杭州湾佳鹏2.5m干部', //数据库标识（生产线名称）
            'DB_TYPE'            => 'sqlsrv', //数据库类型（支持2005,2008）
            'DB_HOST'            => '.\MWJ', //服务器地址
            'DB_USER'            => 'sa', //用户名
            'DB_PWD'             => 'karry', //密码
            'DB_NAME'            => 'cpmsdbfh', //数据库名
            'isnew'              => 1, //老生管0 新生管1
            'updown'             => 0, //单刀0 上下刀1
            'paperCodeNumber'    => 1, //纸质代码占几个字符
            'paperCodeSpaceChar' => '-',
            'socketio'   => [
                'port'   => 8888,//这个端口号好像可以随意配置（不同线不一样）
                'timer'  => 1,//php中间层接受udp的时间间隔（单位：秒，决定了前端页面显示的数据每隔多少秒刷新1次）
                'domain' => 'http://nbhw.leaper.ltd:7000',//'http://test.leaper.ltd:8888',
            ],
            'socket_bind' => [
                'address' => '192.168.16.123',//安装php环境的电脑内网ip
                'port'    => 7000,//“飞机”配置的端口号 有可能要+1（不同线不一样）
                'flag'    => 'DATA1234',//标识符
            ] 
        ],
        [
            'DB_FLAG'            => '2500大线浦江',        //数据库标识（生产线名称）
            'DB_TYPE'            => 'sqlsrv',      //数据库类型（支持2005,2008）
            'DB_HOST'            => '.\MW', //数据库服务器地址
            'DB_USER'            => 'sa',          //数据库用户名
            'DB_PWD'             => 'karry',        //数据库密码
            'DB_NAME'            => 'cpmsdbfh',    //数据库名
            'isnew'              => 0,               //老生管=>0   新生管=>1
            'updown'             => 0,              //单刀  =>0   上下刀=>1
            'paperCodeNumber'    => 1,     //纸质代码占几个字符
            'paperCodeSpaceChar' => '-',
            'socketio'   => [
                'port'   => 8888,         //这个端口号好像可以随意配置（不同线不一样）
                'timer'  => 1,           //php中间层接受udp的时间间隔(单位秒)=>推送时间间隔
                'domain' => 'http://pjhs.leaper.ltd:8888',  //路由器映射的地址
            ],
            'socket_bind' => [
                'address' => '192.168.1.8',               //安装php环境的电脑内网ip
                'port'    => 2001,                             //php监听端口号
                'flag'    => 'Line1:',                         //标识符: 梁工=>DATA1234 || 蔡总=> Line1:
            ],
        ]
        /*[
            'DB_FLAG'            => '新单123', //数据库标识（生产线名称）
            'DB_TYPE'            => 'cpmsdbfh', //数据库类型（支持2005,2008）
            'DB_HOST'            => '.\MWJ', //服务器地址
            'DB_USER'            => 'sa', //用户名
            'DB_PWD'             => 'karry', //密码
            'DB_NAME'            => 'cpmsdbfh', //数据库名
            'isnew'              => 1, //老生管0 新生管1
            'updown'             => 1, //单刀0 上下刀1
            'paperCodeNumber'    => 1, //纸质代码占几个字符
            'paperCodeSpaceChar' => '-',
            'socketio'   => [
                'port'   => 1107,//这个端口号好像可以随意配置（不同线不一样）
                'timer'  => 1,//php中间层接受udp的时间间隔（单位：秒，决定了前端页面显示的数据每隔多少秒刷新1次）
                'domain' => 'http://test.leaper.ltd:1107'//'http://test.leaper.ltd:8888',
            ],
            'socket_bind' => [
                'address' => '192.168.1.212',//'192.168.1.195',//安装php环境的电脑内网ip
                'port'    => 7777,//5001,//“飞机”配置的端口号 有可能要+1（不同线不一样）
                'flag'    => 'Line1:',//标识符
            ]
        ],*/
    ]
];