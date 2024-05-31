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
		if( '1' == config('db_config')[$this->request->post('alter_config_index')]['isnew']){
			try {
				$result = Db::connect($connect)
				->table('SysConfiguration')
				->where(['ParameterType' => '特殊参数', 'ParameterName' => 'TMP_FINISH_TOTAL_LEN_OFFSET'])
				->field('ParameterValue')
				->find();
			} catch ( \Exception $e ) {
				throw new \app\common\exception\SgException([
					'msg'=>'SysConfiguration'.$this->request->lang['table'].$this->request->lang['fetch'].$this->request->lang['fail']
				]);
			}
			if( NULL === $result ){
				throw new \app\common\exception\SgException(['msg'=>'SysConfiguration '.$this->request->lang['table'].$this->request->lang['fetch'].$this->request->lang['fail']]);
			}
			return ['errorCode'=>'00000','msg'=> $this->request->lang['return'] . $this->request->lang['success'],'result'=>$result['ParameterValue'] ];
		}else{
			$field = '1' == config('db_config')[$this->request->post('alter_config_index')]['updown'] ? 'prodlenm' : 'lenmm';
			try {
				$result = Db::connect($connect)
				->table('finish')
				->field('id,'.$field)
				->order('id','desc')
				->find();
			} catch ( \Exception $e ) {
				throw new \app\common\exception\SgException(['msg'=>'finish '.$this->request->lang['table'].$this->request->lang['fetch'].$this->request->lang['fail']]);
			}
			if( NULL === $result ){
				throw new \app\common\exception\SgException(['msg'=>'finish '.$this->request->lang['table'].$this->request->lang['noData']]);
			}
			return ['errorCode'=>'00000','msg'=> $this->request->lang['return'] . $this->request->lang['success'],'result'=>['value'=>$result[$field],'id'=>$result['id']] ];
		}
	}

	public function changeValue()
	{
		$this->validate( $this->request->post(),'app\sg\validate\AlterValidate.changeValue' );
		$connect = util::getConnect(config('db_config')[$this->request->post('change_config_index')]);
		if( 1 == config('db_config')[$this->request->post('change_config_index')]['isnew']){
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
				throw new \app\common\exception\DataBaseException(['msg'=>$this->request->lang['update'].$this->request->lang['fail']]);
			}
			$result = [
				'before' => $data['ParameterValue'],
				'after'  => $this->request->post('change_value'),
				'time'   => date('Y-m-d H:i:s',time())
			];
		}else{
			$field = 1 == config('db_config')[$this->request->post('change_config_index')]['updown'] ? 'prodlenm' : 'lenmm';
			$data = Db::connect($connect)
			->table('finish')
			->where('id', $this->request->post('change_id') )
			->field($field)
			->find();
			if( NULL === $data ){
				throw new \app\common\exception\ParamsException(['msg'=>'change_id'.$this->request->lang['parameter'].$this->request->lang['error']]);
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
				throw new \app\common\exception\DataBaseException(['msg'=>$this->request->lang['update'] . $this->request->lang['fail']]);
			}
			$result = [
				'id'     => $this->request->post('change_id'),
				'before' => $data[$field],
				'after'  => $this->request->post('change_value'),
				'time'   => date('Y-m-d H:i:s',time())
			];
		}
		/*$record = session('record');
		$record[ $this->request->post('change_config_index') ][] = $result;
		session('record',$record);*/
		return ['errorCode'=>'00000','msg'=>$this->request->lang['update'] . $this->request->lang['success'],'result'=>$result ];
	}

	public function getRecord()
	{
		$this->validate( $this->request->post(),'app\sg\validate\AlterValidate.getRecord' );
		$result = session('record')[$this->request->post('alter_config_index')];
		$result = empty($result) ? [] : $result;
		/*$result = [ 
            [ 'id' => 1, 'before' => 1000, 'after' => 1, 'time' => date('Y-m-d H:i:s',time()) ],
            [ 'id' => 2, 'before' => 2000, 'after' => 200, 'time' => date('Y-m-d H:i:s',time())  ],
            [ 'id' => 3, 'before' => 3000, 'after' => 3, 'time' => date('Y-m-d H:i:s',time())  ],
        ];*/
		return ['errorCode'=>'00000','msg'=>$this->request->lang['return'] . $this->request->lang['success'],'result'=>$result];
	}

	public function clearRecord()
	{
		$this->validate( $this->request->post(),'app\sg\validate\AlterValidate.getRecord' );
		$record = session('record');
		$record[ $this->request->post('alter_config_index') ] = NULL;
		session('record',$record);
		return ['errorCode'=>'00000','msg'=>$this->request->lang['delete'] . $this->request->lang['success'],'result'=>NULL];
	}

}