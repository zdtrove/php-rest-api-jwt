<?php
    require_once('constants.php');
    class Rest {
        protected $request;
        protected $serviceName;
        protected $param;

        public function __construct() {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                $this->throwError(REQUEST_METHOD_NOT_VALID, 'Request method is not valid.');
            }
            $handler = fopen('php://input', 'r');
            $this->request = stream_get_contents($handler);
            $this->validateRequest($this->request);
        }

        public function validateRequest($request) {
            if ($_SERVER['CONTENT_TYPE'] !== 'application/json') {
                $this->throwError(REQUEST_CONTENTTYPE_NOT_VALID, 'Request content type is not valid.');
            }
            $data = json_decode($this->request, true);
            if (!isset($data['name']) || $data['name'] === "") {
                $this->throwError(API_NAME_REQUIRED, "Api name is required");
            }
            $this->serviceName = $data['name'];
            if (!is_array($data['param'])) {
                $this->throwError(API_PARAM_REQUIRED, "Api param is required");
            }
            $this->param = $data['param'];
        }

        public function processApi() {
            $api = new API;
            $rMethod = new ReflectionMethod('API', $this->serviceName);
            if (!method_exists($api, $this->serviceName)) {
                $this->throwError(API_DOST_NOT_EXIST, 'API does not exist.');
            }
            $rMethod->invoke($api);
        }

        public function validateParameter($fieldName, $value, $dataType, $required = true) {
            if ($required === true && empty($value) === true) {
                $this->throwError(VALIDATE_PARAMETER_REQUIRED, $fieldName . " parameter is required.");
            }

            switch ($dataType) {
                case BOOLEAN:
                    if (!is_bool($value)) {
                        $this->throwError(VALIDATE_PARAMETER_DATATYPE, "Datatype is not valid for " . $fieldName . '. It should be boolean.');
                    }
                    break;
                case INTEGER:
                    if (!is_numeric($value)) {
                        $this->throwError(VALIDATE_PARAMETER_DATATYPE, "Datatype is not valid for " . $fieldName . '. It should be numberic.');
                    }
                    break;
                case STRING:
                    if (!is_numeric($value)) {
                        $this->throwError(VALIDATE_PARAMETER_DATATYPE, "Datatype is not valid for " . $fieldName . '. It should be numberic.');
                    }
                    break;
                default: 
                    break;
            }
        }

        public function throwError($code, $message) {
            header("content-type: application/json");
            $errorMsg = json_encode([
                'error' => ['status' => $code, 'message' => $message]
            ]);
            echo $errorMsg; exit;
        }

        public function returnResponse() {

        }
    }