<?php
    class Api extends Rest {
        public $dbConn;
        public function __construct() {
            parent::__construct();
            $db = new DbConnect;
            $this->dbConn = $db->connect();
        }

        public function generateToken() {
            $email = $this->validateParameter('email', $this->param['email'], STRING);
            $pass = $this->validateParameter('pass', $this->param['pass'], STRING);
            try {
                $stmt = $this->dbConn->prepare("SELECT * FROM users WHERE email = :email AND password = :pass");
                $stmt->bindParam(":email", $email);
                $stmt->bindParam(":pass", $pass);
                $stmt->execute();
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                if (!is_array($user)) {
                    $this->returnResponse(INVALID_USER_PASS, "Email or Password is incorrect");
                }
                if ($user['active'] == 0) {
                    $this->returnResponse(USER_NOT_ACTIVE, "User is not activated. Please contact to admin.");
                }
                $payload = [
                    'iat' => time(),
                    'iss' => 'localhost',
                    'exp' => time() + (15*60),
                    'userId' => $user['id']
                ];
                $token = JWT::encode($payload, SECRETE_KEY);
                $data = ['token' => $token];
                $this->returnResponse(SUCCESS_RESPONSE, $data);
            } catch (Exception $e) {
                $this->throwError(JWT_PROCESSING_ERROR, $e->getMessage());
            }
        }

        public function addCustomer() {
            $name = $this->validateParameter('name', $this->param['name'], STRING, false);
            $email = $this->validateParameter('email', $this->param['email'], STRING, false);
            $address = $this->validateParameter('address', $this->param['address'], STRING, false);
            $mobile = $this->validateParameter('mobile', $this->param['mobile'], STRING, false);
            try {
                $token = $this->getBearerToken();
                $payload = JWT::decode($token, SECRETE_KEY, ['HS256']);

                $stmt = $this->dbConn->prepare("SELECT * FROM users WHERE id = :userId");
                $stmt->bindParam(":userId", $payload->userId);
                $stmt->execute();
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                if (!is_array($user)) {
                    $this->returnResponse(INVALID_USER_PASS, "This user is not found in our database.");
                }
                if ($user['active'] == 0) {
                    $this->returnResponse(USER_NOT_ACTIVE, "This user may be decactived. Please contact to admin.");
                }
                
                $customer = new Customer;
                $customer->setName($name);
                $customer->setEmail($email);
                $customer->setAddress($address);
                $customer->setMobile($mobile);
                $customer->setCreatedBy($payload->userId);
                $customer->setCreatedOn(date('Y-m-d'));

                if (!$customer->insert()) {
                    $message = 'Failed to insert.';
                } else {
                    $message = 'Inserted successfully.';
                }

                $this->returnResponse(SUCCESS_RESPONSE, $message);
            } catch (Exception $e) {
                $this->throwError(ACCESS_TOKEN_ERRORS, $e->getMessage());
            }
        }
    }