<?php
namespace app\sg\controller;

use think\Controller;
use think\Db;

class Index extends Controller
{
    public function choose()
    {
        var_dump(Db::table('SubFactory')->select());
    }

    public function fetchData()
    {
       	$query_un_car = 'exec dbo.WGetUnCarPackSum @strWhere = N\'' . ' and p.FactoryId=\'\' ' . $this->request->post('factId') . '\'\'' . ' and p.SubFacId=\'\'' . $this->request->post('subId') . '\'\'\'';
        $query_un_sign = 'exec dbo.WGetUnSignDetail @strWhere = N\'' . ' and d.FactoryId= \'\'' . $this->request->post('factId') . '\'\'' . ' and d.SubFacId= \'\'' . $this->request->post('subId') . '\'\'\'';
        try {
            $un_car_data = Db::query($query_un_car);
            $un_sign_data = Db::query($query_un_sign);
        } catch (\Exception $e) {
            var_dump($e->getMessage());
        }
		$un_car = [];
		if( !empty($un_car_data) ){
			foreach ($un_car_data as $key => $value) {
				$un_car[] = [
					$un_car_data[$key]['PCarType'],
					$un_car_data[$key]['iCount'],
					$un_car_data[$key]['To5Area']
				];
			}
		}
        
		$un_sign = [];
		
		if( !empty($un_sign_data) ){
			foreach ($un_sign_data[0] as $idx => $val) {
				$un_sign[] = [
					$un_sign_data[$idx]['CarNo'],
					$un_sign_data[$idx]['strNo'],
					$un_sign_data[$idx]['CarPName'],
					$un_sign_data[$idx]['UnSignCount']
				];
			}
		}
        return ['un_car'=>$un_car,'un_sign'=>$un_sign];
    }
}
