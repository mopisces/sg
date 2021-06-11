<?php
return [
    'sg_user0'     => 'karry/karry',
    'sg_user1'     => 'good/good',
    'sg_user2'     => 'ok/ok',
    'factory_name' => '四川来喜',
    'db_config'    => [
        [
            'DB_FLAG'            => '义乌云门', //数据库标识（生产线名称）
            'DB_TYPE'            => 'sqlsrv', //数据库类型（支持2005,2008）
            'DB_HOST'            => 'LDWIN10\LDWIN102008', //服务器地址
            'DB_USER'            => 'sa', //用户名
            'DB_PWD'             => 'sakey', //密码
            'DB_NAME'            => 'cpmsdbfh', //数据库名
            'DB_DATA'            => 1, //是否从数据库读取数据              
            'isnew'              => 1, //老生管0 新生管1
            'updown'             => 0, //单刀0 上下刀1
            'paperCodeNumber'    => 1, //纸质代码占几个字符
            'paperCodeSpaceChar' => '-',
            'socketio'   => [
                'port'   => 40000,//这个端口号好像可以随意配置（不同线不一样）
                'timer'  => 1,//php中间层接受udp的时间间隔（单位：秒，决定了前端页面显示的数据每隔多少秒刷新1次）
                'domain' => 'http://ywym.leaper.ltd:8010',//'http://test.leaper.ltd:8888',
            ],
            'socket_bind' => [
                'address' => '192.168.1.188',//安装php环境的电脑内网ip
                'port'    => 3000,//“飞机”配置的端口号 有可能要+1（不同线不一样）
                'flag'    => 'DATA1234',//标识符
            ]
        ]/*,
        [
            'DB_FLAG'            => '江苏炫彩BHS', //数据库标识（生产线名称）
            'DB_TYPE'            => 'cpmsdbfh', //数据库类型（支持2005,2008）
            'DB_HOST'            => '192.168.1.209', //服务器地址
            'DB_USER'            => 'sa', //用户名
            'DB_PWD'             => 'karry', //密码
            'DB_NAME'            => 'cpmsdbfh', //数据库名
            'isnew'              => 1, //老生管0 新生管1
            'updown'             => 0, //单刀0 上下刀1
            'paperCodeNumber'    => 1, //纸质代码占几个字符
            'paperCodeSpaceChar' => '-',
            'socketio'   => [
                'port'   => 40001,//这个端口号好像可以随意配置（不同线不一样）
                'timer'  => 1,//php中间层接受udp的时间间隔（单位：秒，决定了前端页面显示的数据每隔多少秒刷新1次）
                'domain' => 'http://jsxc.leaper.ltd:40001'//'http://test.leaper.ltd:8888',
            ],
            'socket_bind' => [
                'address' => '192.168.1.22',//'192.168.1.195',//安装php环境的电脑内网ip
                'port'    => 3000,//5001,//“飞机”配置的端口号 有可能要+1（不同线不一样）
                'flag'    => 'DATA1234',//标识符
            ]
        ]*/
    ]
];