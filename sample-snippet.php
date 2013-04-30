<?php

class ReportsController extends Controller
{
  /**
	 * Declares class-based actions.
	 */
	public function actions()
	{
		return array(
			// captcha action renders the CAPTCHA image displayed on the contact page
			'captcha'=>array(
				'class'=>'CCaptchaAction',
				'backColor'=>0xFFFFFF,
			),
			// page action renders "static" pages stored under 'protected/views/site/pages'
			// They can be accessed via: index.php?r=site/page&view=FileName
			'page'=>array(
				'class'=>'CViewAction',
			),
		);
	}

	public function actionList() {

		(!Yii::app()->user->isGuest) ? '' : $this->redirect(Yii::app()->createUrl('site/logout'));

		$this->layout = '//layouts/home';

		//If a particular latitude and longitude have been selected
		if (isset($_POST['latitude']) && isset($_POST['longitude'])) {
			print file_get_contents('http://maps.google.com/maps/geo?q='.$_POST['latitude'].','.$_POST['longitude'].'&output=json');

		} else {
			$model = new ListForm;

			// Setup for Map View
			$location['zoom'] = 11;
			$location['lat'] = '1.3667'; // set map's center
			$location['lng'] = '103.82';

			//Filtered or all records fetched
			$data = (isset($_POST['startDate'])) ? $model->searchOutage($_POST) : $model->getOutage();
			$reports = (isset($_POST['startDate'])) ? $model->searchOutage($_POST) : $model->getOutage();



			$hasData = (count($data) > 0) ? array('data' => $data, 
												  'reports' => $reports, 
												  'locationZoom' => $location['zoom'], 
												  'locationLat' => $location['lat'], 
												  'locationLng' => $location['lng']) 
			                              : array('noRecords' => 1);
			$this->render('viewreports', $hasData);
		}
	}

	//Determine if the user uses Starhub, Singtel or M1
	public function networkOperator($no) {
		$n = '';
		if (preg_match('/starhub/i', $no) || preg_match('/CallZone/i', $no)) {
			$n .= 0;
		} else if (preg_match('/singtel/i', $no)) {
			$n .= 1;
		} else if (preg_match('/m1/i', $no)) {
			$n .= 2;
		}
		switch ($n) {
			case '0':
			   $flag = 0;
			break;
			case '1':
			   $flag = 1;
			break;
			case '2':
			   $flag = 2;
			break;
			default:
			   $flag = 0;
			break;
		}
		
		return $flag;
	}

	public function getAddressFromLatLong($row, $jsonString) {
		$str = '';
		if (!empty($row)) {
			$str .= $row;
		} else {
			$googleJSON = file_get_contents($jsonString);
			$arrLocation = json_decode($googleJSON, true);
			if (array_key_exists('Placemark', $arrLocation)) {
				foreach ($arrLocation['Placemark'] as $keys => $value) {
					$str .= $value['address'];
				}
			} else $str .= $row;
		}
		return $str;
	}
	
	public function getReportStatus($lat, $lon) {
		$model = new ListForm;
		$data = $model->getReportStatus($lat, $lon);
                $cnt = count($data) - 1;
		$arr = array();
		foreach ($data as $rows) {
		   $arr[] = array(
						    'id' => $rows['id'],
							'loc_lat' => $rows['loc_lat'], 
							'loc_lon' => $rows['loc_lon'], 
							'loc_address' => $rows['loc_address'], 
							'voice_cannot_call' => ($this->removeDuplicateStatus('voice_cannot_call', $rows['voice_cannot_call'], $rows['loc_lat'], $rows['loc_lon'], $rows['id']) == 1) ? 0 : $rows['voice_cannot_call'], 
							'voice_call_dropped' => ($this->removeDuplicateStatus('voice_call_dropped', $rows['voice_call_dropped'], $rows['loc_lat'], $rows['loc_lon'], $rows['id']) == 1) ? 0 : $rows['voice_call_dropped'], 
							'voice_call_poor' => ($this->removeDuplicateStatus('voice_call_poor', $rows['voice_call_poor'], $rows['loc_lat'], $rows['loc_lon'], $rows['id']) == 1) ? 0 : $rows['voice_call_poor'], 
							'voice_no_signal' => ($this->removeDuplicateStatus('voice_no_signal', $rows['voice_no_signal'], $rows['loc_lat'], $rows['loc_lon'], $rows['id']) == 1) ? 0 : $rows['voice_no_signal'], 
							'data_cannot_access' => ($this->removeDuplicateStatus('data_cannot_access', $rows['data_cannot_access'], $rows['loc_lat'], $rows['loc_lon'], $rows['id']) == 1) ? 0 : $rows['data_cannot_access'], 
							'data_slow_access' => ($this->removeDuplicateStatus('data_slow_access', $rows['data_slow_access'], $rows['loc_lat'], $rows['loc_lon'], $rows['id']) == 1) ? 0 : $rows['data_slow_access']
						  
						  );
		}
                 
		return $arr;
	}
        

	public function getDuplicateLatLong($lat, $lon) {
		$model = new ListForm;
		$data = $model->countReportsSameLatLon($lat, $lon);
		$cnt = '';
		foreach ($data as $rows) {
			$cnt .= $rows['cnt'];
		}
		return $cnt;
	}
	
	public function removeDuplicateStatus($field, $status, $lat, $lon, $id) {
		$model = new ListForm;
		$data = $model->removeDuplicateStatus($field, $status, $lat, $lon, $id);
		$rs = '';
		foreach ($data as $rows) {
			switch ($field) {
				case 'voice_cannot_call':
				   $rs .= $rows['voice_cannot_call'];
				break;
				case 'voice_call_dropped':
				   $rs .= $rows['voice_call_dropped'];
				break;
				case 'voice_call_poor':
				   $rs .= $rows['voice_call_poor'];
				break;
				case 'voice_no_signal':
				   $rs .= $rows['voice_no_signal'];
				break;
				case 'data_cannot_access':
				   $rs .= $rows['data_cannot_access'];
				break;
				case 'data_slow_access':
				   $rs .= $rows['data_slow_access'];
				break;
			}
		}
		return $rs;
	}

    public function actionViewrecent() {
	    $model = new ListForm;
	    $data = $model->getOutage();
		$hasData = (count($data) > 0) ? array('data' => $data) : array('noRecords' => 1);
		$this->renderPartial('viewrecent', $hasData, false, true);
	}

	public function actionSearchform() {

		(!Yii::app()->user->isGuest) ? '' : $this->redirect(Yii::app()->createUrl('site/logout'));

		$this->layout = '//layouts/home';
		$data = null;
		$model = new ListForm;
		// Setup for Map View
		$location['zoom'] = 11;
		$location['lat'] = '1.3667'; // set map's center
		$location['lng'] = '103.82';
		$dataLocation = ListForm::model()->findAll();
		$dataDeviceModel = ListForm::model()->findAll(array('condition' => '', 'group' => 'device_handset_model'));

                $context  = stream_context_set_default(
                                                        array(
                                                          'http' => array(
                                                            'header' => 'Authorization: Basic ' . base64_encode('staging'.':'.'staging1234')
                                                          )
                                                        )
                                                      );
                $areas = file_get_contents(Yii::app()->getBaseUrl(true).'/json/areas.json');
                $objArea = json_decode($areas, true);
                //print_r($objAreas); exit;
				
				$reports = (isset($_POST['startDate'])) ? $model->searchOutage($_POST) : $model->getOutage();
				
                $this->render('searchreports', array('objRegions' => $objArea['regions'],
						     'objAreas' => $objArea['areas'],
						     'dataLocation' => $dataLocation,
						     'dataDeviceModel' => $dataDeviceModel,
						     'reports' => $reports,
						     'locationZoom' => $location['zoom'], 
													 'locationLat' => $location['lat'], 
													 'locationLng' => $location['lng']));
	}

	public function actionSearch() {
	    $model = new ListForm;
	    $data = $model->searchOutage($_POST);
		$hasData = (count($data) > 0) ? array('data' => $data) : array('noRecords' => 1);
		$this->render('viewreports', $hasData);
	}

	public function actionExportReports() {
		   date_default_timezone_set('Asia/Singapore');
           ob_start();
           $model = new ListForm;
           Yii::import('ext.ESCVExport');
           $m = $this->loadModel();
		   $dataProvider = $model->reportsToExportCSV($_GET['id']);
           //$dataProvider = ($_GET['id'] != 0) ? $model->reportsToExportCSV($m->id) : $model->reportsToExportCSV(0);
           $data = array();
           $arrSubHeader = array();
		   array_push($arrSubHeader,
                         'id',
			 'created_date',
                         'report_is_past',
                         'network_system',
                         "network_operator",
                         "network_cell_id",
                         'network_rscp',
                         'voice_cannot_call',
                         'voice_call_dropped',
			 'voice_call_poor',
                         'voice_no_signal',
                         'data_cannot_access',
                         "data_slow_access",
                         "data_download",
                         'data_upload',
                         'data_ping',
                         'other_issues',
			 'loc_lat',
                         'loc_lon',
                         'loc_height',
                         "loc_address",
                         "loc_type",
                         'regions',
                         'district_areas',
                         'gcm_id',
			 'apns_id',
                         'device_type',
                         'device_handset_model',
                         "device_hardware_model",
                         "device_version"
              );
           array_push($data, $arrSubHeader);
           foreach ($dataProvider as $rows) {
              $dateCreated = mktime(0, 0, 0, substr($rows['created_date'], 5, 2), substr($rows['created_date'], 8, 2), substr($rows['created_date'], 0, 4));
              array_push($data,
                         array(
	                         $rows['id'],
	                         date('d F Y', $dateCreated),
	                         $rows['report_is_past'],
	                         $rows['network_system'],
	                         $rows['network_operator'],
	                         $rows['network_cell_id'],
	                         $rows['network_rscp'],
	                         $rows['voice_cannot_call'],
                                 $rows['voice_call_dropped'], 
                                 $rows['voice_call_poor'],
			         $rows['voice_no_signal'],
				 $rows['data_cannot_access'],
				 $rows["data_slow_access"],
				 $rows["data_download"],
				 $rows['data_upload'],
				 $rows['data_ping'],
				 $rows['other_issues'],
				 $rows['loc_lat'],
				 $rows['loc_lon'],
				 $rows['loc_height'],
				 $rows["loc_address"],
				 $rows["loc_type"],
				 $rows['regions'],
				 $rows['district_areas'],
				 $rows['gcm_id'],
				 $rows['apns_id'],
				 $rows['device_type'],
				 $rows['device_handset_model'],
				 $rows["device_hardware_model"],
				 $rows["device_version"]    				
							 				 			 
                         )
              );
           }
		   $csv = new ESCVExport($data);
			   $filename = 'reports-'.date('d-m-Y').'.csv';
		   Yii::app()->getRequest()->sendFile($filename, $csv->output(), "text/csv", false);
		   ob_end_flush();
		   exit;
        }

    public function actionMap() {
	    $model = $this->loadModelMap();
		if (count($model) > 0)
	       $this->renderPartial('map', array(
	                                'data' => $model
	                                  ), false, true);
		else throw new CHttpException(600,'There is no related record.'); //600 -> http exception customized code
	}

	public function actionSaveResponses() {
	   $data = new NetworkUserForm;
	   if (isset($_POST['NetworkUserForm'])) {

	                    $data->name = $_POST['NetworkUserForm']['name'];
	                    $data->positionTitle = '';
	                    $data->response = $_POST['NetworkUserForm']['response'];
	                    $data->outageID = $_POST['NetworkUserForm']['outageID'];
	                    $data->dateCreated = date('Y-m-d H:i:s');
			    $data->save();
	                    $url = Yii::app()->createUrl('reports/list'); //with query string for modifying the existing record
		  	    $this->redirect($url);
		    }

	    $this->renderPartial('editoutage', array(
	                                              'outage' => $data,
						      'id' => $_GET['id']
	                                              ), false, true);
	}


	/**
	 * This is the action to handle external exceptions.
	 */
	public function actionError()
	{
	    if($error=Yii::app()->errorHandler->error)
	    {
	    	if(Yii::app()->request->isAjaxRequest)
	    		echo $error['message'];
	    	else
	        	$this->render('error', $error);
	    }
	}




        /**
	 * Returns the data model based on the primary key given in the GET variable.
	 * If the data model is not found, an HTTP exception will be raised.
	 */

	public function loadModelIDs()
	{
	    if(isset($_GET['ids']))
            {
		$condition = '';
		$id = NetworkUserForm::model()->findByPk($_GET['id'], $condition);
                return $id;
	    }
	    else throw new CHttpException(404,'The requested page does not exist.');
	}

	public function loadModelMap()
	{
	    if(isset($_GET['id']))
            {
		$condition = '';
		$id = ListForm::model()->findByPk($_GET['id'], $condition);
                return $id;
	    }
	    else throw new CHttpException(404,'The requested page does not exist.');
	}

	public function loadModel()
	{
	    if(isset($_GET['id']))
            {
		$condition = '';
		$id = NetworkUserForm::model()->findByPk($_GET['id'], $condition);
                return $id;
	    }
	    else throw new CHttpException(404,'The requested page does not exist.');
	}

}

?>
