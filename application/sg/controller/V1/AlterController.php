<?php
namespace app\sg\controller\v1;

use think\Controller;
use think\Db;
use util;

class AlterController extends Controller
{
	protected $failException = true;

	public function getValue()
	{
		$this->validate( $this->request->post(),'app\sg\validate\AlterValidate.getConfig' );
		$connect = util::getConnect(config('db_config')[$this->request->post('alter_config_index')]);
		if( '1' === config('db_config')[$this->request->post('alter_config_index')]['isnew']){
			try {
				$result = Db::connect($connect)
				->table('SysConfiguration')
				->where(['ParameterType' => '特殊参数', 'ParameterName' => 'TMP_FINISH_TOTAL_LEN_OFFSET'])
				->field('ParameterValue')
				->find();
			} catch ( \Exception $e ) {
				throw new \app\common\exception\SgException(['msg'=>'SysConfiguration 表数据获取失败']);
			}
			if( NULL === $result ){
				throw new \app\common\exception\SgException(['msg'=>'SysConfiguration 表没有对应参数']);
			}
			return ['errorCode'=>'00000','msg'=>'返回成功','result'=>$result['parametervalue'] ];
		}else{
			$field = '1' === config('db_config')[$this->request->post('alter_config_index')]['updown'] ? 'prodlenm' : 'lenmm';
			try {
				$result = Db::connect($connect)
				->table('finish')
				->field('id,'.$field)
				->order('id','desc')
				->find();
			} catch ( \Exception $e ) {
				throw new \app\common\exception\SgException(['msg'=>'finish 表数据获取失败']);
			}
			if( NULL === $result ){
				throw new \app\common\exception\SgException(['msg'=>'finish 表没有对应参数']);
			}
			return ['errorCode'=>'00000','msg'=>'返回成功','result'=>$result ];
		}
	}

	public function changeValue()
	{
		$this->validate( $this->request->post(),'app\sg\validate\AlterValidate.changeValue' );
		$connect = util::getConnect(config('db_config')[$this->request->post('change_config_index')]);
		if( 1 === config('db_config')[$this->request->post('change_config_index')]['isnew']){
			$data = Db::connect($connect)
			->table('SysConfiguration')
			->where(['ParameterType' => '特殊参数','ParameterName' => 'TMP_FINISH_TOTAL_LEN_OFFSET'])
			->field('ParameterValue')
			->find();
			$update = Db::connect($connect)
			->table('SysConfiguration')
			->where(['ParameterType' => '特殊参数','ParameterName' => 'TMP_FINISH_TOTAL_LEN_OFFSET'])
			->data(['ParameterValue'=>$this->request->post('change_value')])
			->update();
			if( 1 !== $update ){
				throw new \app\common\exception\DataBaseException(['msg'=>'数据更新失败']);
			}
			$result = [
				'before' => $data,
				'after'  => $this->request->post('change_value'),
				'time'   => date('Y-m-d H:i:s',time())
			];
		}else{
			$field = 1 === config('db_config')[$this->request->post('change_config_index')]['updown'] ? 'prodlenm' : 'lenmm';
			$data = Db::connect($connect)
			->table('finish')
			->where('id', $this->request->post('change_id') )
			->field($field)
			->find();
			if( NULL === $data ){
				throw new \app\common\exception\ParamsException(['msg'=>'change_id参数错误']);
			}
			try {
				$update = Db::connect($connect)
				->table('finish')
				->where('id', $this->request->post('change_id') )
				->data([$field=>$this->request->post('change_value')])
				->update();
			} catch ( Exception $e ) {
				throw new \app\common\exception\ExecException();
			}
			if( 1 !== $update ){
				throw new \app\common\exception\DataBaseException(['msg'=>'数据更新失败']);
			}
			$result = [
				'id'     => $this->request->post('change_id'),
				'before' => $data[$field],
				'after'  => $this->request->post('change_value'),
				'time'   => date('Y-m-d H:i:s',time())
			];
		}
		$record = session('record');
		$record[ $this->request->post('change_config_index') ][] = $result;
		session('record',$record);
		return ['errorCode'=>'00000','msg'=>'更新成功','result'=>$result ];
	}

	public function getRecord()
	{
		$this->validate( $this->request->post(),'app\sg\validate\AlterValidate.getRecord' );
		$result = session('record')[$this->request->post('alter_config_index')];
		$result = [ 
            [ 'id' => 1, 'before' => 1000, 'after' => 1, 'time' => 1 ],
            [ 'id' => 2, 'before' => 2000, 'after' => 200, 'time' => 2000 ],
            [ 'id' => 3, 'before' => 3000, 'after' => 3, 'time' => 3 ],
        ];
		return ['errorCode'=>'00000','msg'=>'返回成功','result'=>$result];
	}

	public function clearRecord()
	{
		$this->validate( $this->request->post(),'app\sg\validate\AlterValidate.getRecord' );
		$record = session('record');
		$record[ $this->request->post('alter_config_index') ] = NULL;
		session('record',$record);
		return ['errorCode'=>'00000','msg'=>'删除成功','result'=>NULL];
	}

}