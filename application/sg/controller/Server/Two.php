<?php
namespace app\sg\controller\Server;

use think\Controller;
use app\sg\controller\Server\Core;

class One extends Controller
{
    public $config_index = 1;

    public function index(Core $core)
    {
        $core->index($this->config_index);
    }

}
