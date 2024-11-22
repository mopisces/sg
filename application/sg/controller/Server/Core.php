<?php
namespace app\sg\controller\Server;

use Workerman\Worker;
use PHPSocketIO\SocketIO;
use Workerman\Lib\Timer;
use think\Controller;
use app\common\udp\AnalyzeData;
use util;

class Core extends Controller
{
    protected $socket;
    protected $config_index = NULL;
    protected $shiftAvgSpeed = 1;

    public function index($config_index)
    {
        $this->config_index = $config_index;
        $config = config('db_config')[ $this->config_index ];
        $this->socket = new SocketIO($config['socketio']['port']);
        $this->socket->on('workerStart', function($socket)use($config){
            Timer::add(1,function()use($config){
                if( $config['DB_DATA'] ){
                    $data = $this->getDataFromDB();
                }else{
                    $data = $this->analyzeUDP();
                }
                var_dump($data);
                $this->socket->emit('AnalyUdpData'.$this->config_index, $data );
            });
            if($this->config_index == 0) {
                Timer::add(5, function()use($config){
                    $emitStr = json_encode([
                        'ret'=> 0,
                        'data'=> null,
                        'msg'=> ""
                    ]);
                    try {
                        $conn = sqlsrv_connect($config['DB_HOST'],[
                            'Database'=> $config['DB_NAME'],
                            'UID'=> $config['DB_USER'],
                            'PWD'=> $config['DB_PWD'],
                            "CharacterSet"=> "UTF-8"
                        ]);
                        if( $conn === false ){
                            return json_encode(['ret'=>0,'data'=>NULL,'msg'=>'check line status']);;
                        }
                        $stmt = sqlsrv_query( $conn, "SELECT TOP 20 *, ISNULL(CASE 
                        WHEN (paper_len / 1000.0 * cutting_qty) > (paper_len2 / 1000.0 * cutting_qty2) 
                        THEN (paper_len / 1000.0 * cutting_qty * width / 1000.0) 
                        ELSE (paper_len2 / 1000.0 * cutting_qty2 * width / 1000.0) 
                        END, 0) AS calc_ord_area FROM MyOrder WITH (NOLOCK) ORDER BY sn ASC" );
                        if( $stmt === false ){
                            return json_encode(['ret'=>0,'data'=>NULL,'msg'=>'check table proddata']);;
                        }
                        $areaAdd = 0;
                        $lenAdd = 0;
                        $startTime = '';
                        $detail = [];
                        $preRowEndTime = time();
                        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                            $rowData = [];
                            $rowData['order_date'] = $row['order_date']?$row['order_date']->format("Y-m-d H:i:s"):"";
                            $rowData['ask_date'] = $row['ask_date']?$row['ask_date']->format("Y-m-d"):"";
                            $rowData['order_date2'] = $row['order_date2']?$row['order_date2']->format("Y-m-d H:i:s"):"";
                            $rowData['ask_date2'] = $row['ask_date2']?$row['ask_date2']->format("Y-m-d"):"";
                            $rowData['first_time'] = $row['first_time']?$row['first_time']->format("Y-m-d H:i:s"):"";
                            $rowData['last_time'] = $row['last_time']?$row['last_time']->format("Y-m-d H:i:s"):"";
                            $rowData['start_time'] = $row['start_time']?$row['start_time']->format("H:i:s"):"";

                            $mergeRow = $this->deelWithRow(array_replace($row, $rowData));
                            $areaAdd += $mergeRow['calc_ord_area'];
                            $lenAdd += $mergeRow['total_len'];
                            $mergeRow['area_add_up'] = (int)$areaAdd;
                            $mergeRow['len_add_up'] = (int)$lenAdd;
                            $mergeRow['calc_ord_area'] = (int)$mergeRow['calc_ord_area'];
                            // 计算预计开始和结束时间\
                            if( $mergeRow['start_time'] ) {
                                $mergeRow['calc_end_time'] = date('H:i:s', strtotime($mergeRow['start_time']) + ($mergeRow['total_len'] / ($this->shiftAvgSpeed == 0 ? 1 : $this->shiftAvgSpeed )* 60) );
                            } else {
                                $mergeRow['start_time'] = $preRowEndTime;
                                $mergeRow['calc_end_time'] = date('H:i:s', strtotime($mergeRow['start_time']) + ($mergeRow['total_len'] / ($this->shiftAvgSpeed == 0 ? 1 : $this->shiftAvgSpeed ) * 60) );
                            }
                            $preRowEndTime = $mergeRow['calc_end_time'];
                            $detail[] = $mergeRow;
                        }
                        sqlsrv_free_stmt($stmt);
                        sqlsrv_close($conn);
                        $emitStr = json_encode([
                            'ret'=> 1,
                            'data'=> $detail,
                            'msg'=> 'success'
                        ]);
                    } catch(\Exception $e) {
                        $emitStr = json_encode([
                            'ret'=> 0,
                            'data'=> null,
                            'msg'=> $e->getMessage()
                        ]);
                    }
                    $this->socket->emit('MyOrderUdp', $emitStr);

                });
            }
        });
        $this->socket->on('disconnect', function($socket){
            var_dump('disconnect');
        });
        Worker::runAll();
    }

    public function deelWithRow( $rowData ) 
    {
        foreach( $rowData as $key=> $value ) {
            $strChartSet = strtoupper(strtoupper(mb_detect_encoding($value, mb_list_encodings(), true)));
            if(  $strChartSet != 'ASCII' || $strChartSet != 'UTF-8') {
               $rowData[$key] = mb_convert_encoding($value, 'UTF-8', $strChartSet);
            }
        }
        return $rowData;
    }

    protected function analyzeUDP()
    {
        $result = AnalyzeData::analyzeUdp($this->config_index);
        try {
            $this->shiftAvgSpeed = $result['data']?$result['data']['benban']['js'] : 1;
        }catch(\Exception $e) {
            $this->shiftAvgSpeed = 1;
            var_dump($e->getMessage());
        }
     
        return json_encode($result);
    }

    protected function getDataFromDB()
    {
        $config = config('db_config')[ $this->config_index ];
        try {
            $conn = sqlsrv_connect($config['DB_HOST'],[
                'Database'=> $config['DB_NAME'],
                'UID'=> $config['DB_USER'],
                'PWD'=> $config['DB_PWD'],
            ]);
            if( $conn === false ){
                return json_encode(['ret'=>0,'data'=>NULL,'msg'=>'check line status']);;
            }
            $stmt = sqlsrv_query( $conn, "SELECT data FROM proddata WITH (NOLOCK) WHERE id = 1" );
            if( $stmt === false ){
                return json_encode(['ret'=>0,'data'=>NULL,'msg'=>'check table proddata']);;
            }

            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $stringData = strval($row['data']);
                $hex = strtoupper(bin2hex($stringData));
                return $this->parseData(hex2bin($hex), $conn);
            }

            sqlsrv_free_stmt($stmt);
            sqlsrv_close($conn);
        } catch ( \Exception $e) {
            return json_encode(['ret'=>0,'data'=>NULL, 'msg'=>$e->getMessage()]);;
        }
    }

    protected function parseData($data, $conn) 
    {
        $offset = 0;
        $areaInfo = ['totalCombinedArea'=> 0, 'benbiArea'=> 0];
        try {
            // 判断存储过程是否存在
            $query = "SELECT COUNT(*) AS exists_count FROM INFORMATION_SCHEMA.ROUTINES WHERE ROUTINE_TYPE = 'PROCEDURE' AND ROUTINE_NAME = ?";
            $stmt = sqlsrv_prepare($conn, $query, array('P_GetAreaInfo'));
            if ($stmt !== false) {
                sqlsrv_execute($stmt);
                $result = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
                if ($result['exists_count'] > 0) {
                    $stmt = sqlsrv_query( $conn, 'EXEC P_GetAreaInfo' );
                    if( $stmt !== false ) {
                        $areaInfo = sqlsrv_fetch_array( $stmt, SQLSRV_FETCH_ASSOC);
                        sqlsrv_free_stmt($stmt);
                        sqlsrv_close($conn);
                    }
                }
            }
        } catch ( \Exception $e) {
            var_dump($e->getMessage());
        }
        // 头部字段字段名称 
        $headerFields = [ 
            "updateUniqueCode", // 数据更新唯一识别码 
            "plcTimeout", // 检测是否有纸 

            //"plcGoodQty", // plc良品数 
            "scds", // plc良品数 

            //"plcRemainQty", // plc剩余数 
            "syds",// plc剩余数 

            //"plcBadQty", // plc坏品数 
            "blds", // plc坏品数 

            //"plcCuttingLen", // plc切长毫米 
            "qc", //plc切长毫米 

            "plcRemainLen", // plc剩余长度毫米 
            "stackingQty", // plc堆数 
            "bundlingQty", // plc捆数 
            "plcCuttingWasteQty", // plc切费张数 
            "paperLen", // pc纸长度 

            //"cuttingQty", // pc切刀数 
            "qds", // pc切刀数 

            "cuttingLen", // pc切长 
            
            //"goodLen", // pc良品长度毫米 
            "ddc", //pc良品长度毫米 
            
            //"remainLen", // pc剩余长度毫米
            "ddsy", // pc剩余长度毫米2 

            //"plcGoodQty2", // plc良品数2 
            "scds2", //plc良品数2 

            //"plcRemainQty2", // plc剩余数2 
            "syds2", // plc剩余数2 

            //"plcBadQty2", // plc坏品数2 
            "blds2", // plc坏品数2

            //"plcCuttingLen2", // plc切长毫米2 
            "qc2", // plc切长毫米2 

            "plcRemainLen2", // plc剩余长度毫米2 
            "stackingQty2", // plc堆数2 
            "bundlingQty2", // plc捆数2 
            "plcCuttingWasteQty2", // plc切费张数2 
            "paperLen2", // pc纸长度2 

            //"cuttingQty2", // pc切刀数2 
            "qds2", // pc切刀数2 

            "cuttingLen2", // pc切长2 
            "goodLen2", // pc良品长度毫米2 
            "remainLen2", // pc剩余长度毫米2 
            "plcCuttingOffQty", // plc切断数量 
            "maxSpeed", // pc最大速度 
            "cruisingSpeed", // pc巡航速度 

            //"speed", // 车速 
            "cs", // 车速 

            "preSpeed", // 预设速度 
            "width", // 门幅
            "plcChangeRemainlen" // plc换单长度
        ];
        // 本班数据
        $currentShiftFields = [
            "zms", // 总长 
            "sc", // 生产米数 
            "sy", // 剩余米数 
            "bl", // 坏品米数 
            "scpf", // 生产平方米 
            "scjpf", // 生产净平方米
            "badSqm", // 坏纸平方 
            "trimSqm", // 修边平方 
            "hzl", // 坏纸率 
            "xbl", // 修边率 
            "js", // 平均速度 
            "scsj", // 生产时间 
            "tcsj", // 停车时间 
            "tccs" // 停车次数
            /*"totalLenm", // 总长 
            "goodLenm", // 生产米数 
            "remainLenm", // 剩余米数 
            "badLenm", // 坏品米数 
            "prodSqm1", // 生产平方米 
            "prodSqm2", // 生产净平方米 
            "badSqm", // 坏纸平方 
            "trimSqm", // 修边平方 
            "badR", // 坏纸率 
            "trimR", // 修边率 
            "avgSpeed", // 平均速度 
            "workTime", // 生产时间 
            "stopTime", // 停车时间 
            "stops", // 停车次数*/
        ];
        // 本笔数据
        $currentEntryField = [
            "zms", // 总长 
            "sc", // 生产米数 
            "sy", // 剩余米数 
            "bl", // 坏品米数 
            "scpf", // 生产平方米 
            "scjpf", // 生产净平方米
            "badSqm", // 坏纸平方 
            "trimSqm", // 修边平方 
            "hzl", // 坏纸率 
            "xbl", // 修边率 
            "js", // 平均速度 
            "scsj", // 生产时间 
            "tcsj", // 停车时间 
            "tccs" // 停车次数
            /*"totalLenm", // 总长 
            "goodLenm", // 生产米数 
            "remainLenm", // 剩余米数 
            "badLenm", // 坏品米数 
            "prodSqm1", // 生产平方米 
            "prodSqm2", // 生产净平方米 
            "badSqm", // 坏纸平方 
            "trimSqm", // 修边平方 
            "badR", // 坏纸率 
            "trimR", // 修边率 
            "avgSpeed", // 平均速度 
            "workTime", // 生产时间 
            "stopTime", // 停车时间 
            "stops", // 停车次数
            */
        ];

        // 机台信息
        $machineListFileds = [
            "huji",
            "kj1Wz",
            "SF1",
            "kj2Wz",
            "SF2",
            "kj3Wz",
            "SF3",
        ];

        // 机台用纸明细
        $machineDetailFileds = [
            "status", // 状态 

            //"speed", // 速度 
            "cs", // 速度 

            "preSpeed", // 预设速度 
            "bridge", // 天桥米数 
            "bridgeOffset", // 天桥补正次数 
            "bridgeOffsetSuccess", // 天桥补正成功次数 

            //"remainLen", // 剩余长度 
            "sy", // 剩余长度 
            //"sumTotalLen", // 生产累计总长
            "lj", // 生产累计总长 

            "code1", // 材质代码 
            "width1", // 本批门幅 
            "totalLen1", // 本批总长 
            "fluteRate1", // 楞率 
            "code2", // 下批材质代码 
            "width2", // 下批门幅 
            "totalLen2", // 下批总长 
            "fluteRate2", // 楞率
        ];

        // 解析前140个字节的整型数据 
        $header = []; 
        for ($i = 0; $i < 35; $i++) { 
            $header[$headerFields[$i]] = unpack("L", substr($data, $offset, 4))[1]; 
            $offset += 4; 

            if( in_array($headerFields[$i], ["ddsy", "ddc"]) ) {
                $header[$headerFields[$i]] = round($header[$headerFields[$i]]/1000, 0);
            }
        }
        // 解析本班数据（56字节） 
        $currentShift = []; 
        for ($i = 0; $i < 14; $i++) { 
            $currentShift[$currentShiftFields[$i]] = unpack("L", substr($data, $offset, 4))[1]; 
            $offset += 4; 
            if(in_array($currentShiftFields[$i], ["hzl", "xbl"])) {
                $currentShift[$currentShiftFields[$i]] = $currentShift[$currentShiftFields[$i]]/100;
            }
            if( $currentShiftFields[$i] == "js" ) {
                $currentShift[$currentShiftFields[$i]] = $currentShift[$currentShiftFields[$i]]/10;
            }
        }
        $currentShift["zmj"] = $areaInfo["totalCombinedArea"];

        // 解析本笔数据（56字节） 
        $currentEntry = []; 
        for ($i = 0; $i < 14; $i++) { 
            $currentEntry[$currentEntryField[$i]] = unpack("L", substr($data, $offset, 4))[1]; 
            $offset += 4; 

            if(in_array($currentEntryField[$i], ["hzl", "xbl"])) {
                $currentEntry[$currentEntryField[$i]] = $currentEntry[$currentEntryField[$i]]/100;
            }
            if( $currentEntryField[$i] == "js" ) {
                $currentEntry[$currentEntryField[$i]] = $currentEntry[$currentEntryField[$i]]/10;
            }
        }
        $currentEntry["zmj"] = $areaInfo["benbiArea"];

        // 解析机台用纸明细数据（7组，每组64字节） 
        $machineDetails = []; 
        for ($i = 0; $i < 7; $i++) { 
            $detail = []; 
            for ($j = 0; $j < 16; $j++) {
                // 特殊处理第9和第13个字段为字符串 
                if ($j == 8 || $j == 12) { 
                    $asciiString = substr($data, $offset, 4); 
                    $detail[$machineDetailFileds[$j]] = trim($asciiString); 
                } else { 
                    $detail[$machineDetailFileds[$j]] = unpack("L", substr($data, $offset, 4))[1]; 
                    if(in_array($machineDetailFileds[$j], ["sy", "lj"])) {
                        $detail[$machineDetailFileds[$j]] = round($detail[$machineDetailFileds[$j]]/1000,0);
                    }
                }
                $offset += 4;
            }
            // 添加到详情数组中 
            $machineDetails[$machineListFileds[$i]] = $detail;
        }
        // 解析班次
        $shiftCode = trim(substr($data, $offset, 4));
        $offset += 4;
        // 解析最后20字节的换班时间字符串 
        $shiftTime = trim(substr($data, $offset, 20));
        // 返回解析结果 
        /*return [ 
            'header' => $header, 
            'currentShift' => $currentShift, 
            'currentEntry' => $currentEntry, 
            'machineDetails' => $machineDetails, 
            'shiftCode' => $shiftCode,
            'shiftTime' => $shiftTime 
        ];*/
        $header["class"] = $shiftCode;
        $header["benban"] = $currentShift;
        $header["benbi"] = $currentEntry;
        $header["huji"] = $machineDetails["huji"];
        $header["SF1"] = $machineDetails["SF1"];
        $header["SF2"] = $machineDetails["SF2"];
        $header["SF3"] = $machineDetails["SF3"];
        return json_encode([
            "ret"=> 1,
            "data"=> $header,
            "msg"=> ""
        ]);
    }
           
    protected static function getAreaInfo( $config ) 
    {
        $conn = sqlsrv_connect($config['DB_HOST'],[
            'Database'=> $config['DB_NAME'],
            'UID'=> $config['DB_USER'],
            'PWD'=> $config['DB_PWD'],
            'CharacterSet'=> 'UTF-8'
        ]);
        $areaInfo = ['totalCombinedArea'=> 0, 'benbiArea'=> 0];
        try {
            // 判断存储过程是否存在
            $query = "SELECT COUNT(*) AS exists_count FROM INFORMATION_SCHEMA.ROUTINES WHERE ROUTINE_TYPE = 'PROCEDURE' AND ROUTINE_NAME = ?";
            $stmt = sqlsrv_prepare($conn, $query, array('P_GetAreaInfo'));
            if ($stmt !== false) {
                sqlsrv_execute($stmt);
                $result = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
                if ($result['exists_count'] > 0) {
                    $stmt = sqlsrv_query( $conn, 'EXEC P_GetAreaInfo' );
                    if( $stmt !== false ) {
                        $areaInfo = sqlsrv_fetch_array( $stmt, SQLSRV_FETCH_ASSOC);
                        sqlsrv_free_stmt($stmt);
                        sqlsrv_close($conn);
                    }
                }
            }
        } catch ( \Exception $e) {
            return $areaInfo;
        }
        return $areaInfo;
    }

    protected function byteTostr($hex)
    {
        $str = '';
        for( $i = 0; $i < strlen( $hex ) - 1; $i += 2 ){
            $str .= chr( hexdec( $hex[ $i ].$hex[ $i + 1 ] ) );
        }
        return $str;
    }
}


