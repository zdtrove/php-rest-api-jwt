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
                    'exp' => time() + (150*60),
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
            $customer = new Customer;
            $customer->setName($name);
            $customer->setEmail($email);
            $customer->setAddress($address);
            $customer->setMobile($mobile);
            $customer->setCreatedBy($this->userId);
            $customer->setCreatedOn(date('Y-m-d'));

            if (!$customer->insert()) {
                $message = 'Failed to insert.';
            } else {
                $message = 'Inserted successfully.';
            }

            $this->returnResponse(SUCCESS_RESPONSE, $message);
        }

        public function getCustomerDetails() {
            $customerId = $this->validateParameter('customerId', $this->param['customerId'], INTEGER);
            $customer = new Customer;
            $customer->setId($customerId);
            $cust = $customer->getCustomerDetailsById();
            if (!is_array($cust)) {
                $this->returnResponse(SUCCESS_RESPONSE, [
                    'message' => 'Customer details not found.'
                ]);
            }
            $response['customerId'] = $cust['id'];
            $response['cutomerName'] = $cust['name'];
            $response['email'] = $cust['email'];
            $response['mobile'] = $cust['mobile'];
            $response['address'] = $cust['address'];
            $response['createdBy'] = $cust['created_user'];
            $response['lastUpdatedBy'] = $cust['updated_user'];
            $this->returnResponse(SUCCESS_RESPONSE, $response);
        }

        public function updateCustomer() {
            $customerId = $this->validateParameter('customerId', $this->param['customerId'], INTEGER);
            $name = $this->validateParameter('name', $this->param['name'], STRING, false);
            $address = $this->validateParameter('address', $this->param['address'], STRING, false);
            $mobile = $this->validateParameter('mobile', $this->param['mobile'], STRING, false);
            $customer = new Customer;
            $customer->setId($customerId);
            $customer->setName($name);
            $customer->setAddress($address);
            $customer->setMobile($mobile);
            $customer->setUpdatedBy($this->userId);
            $customer->setUpdatedOn(date('Y-m-d'));

            if (!$customer->update()) {
                $message = 'Failed to update.';
            } else {
                $message = 'Updated successfully.';
            }

            $this->returnResponse(SUCCESS_RESPONSE, $message);
        }

        public function deleteCustomer() {
            $customerId = $this->validateParameter('customerId', $this->param['customerId'], INTEGER);
            $cust = new Customer;
            $cust->setId($customerId);
            if (!$cust->delete()) {
                $message = 'Failed to delete.';
            } else {
                $message = 'Deleted successfully.';
            }
            $this->returnResponse(SUCCESS_RESPONSE, $message);
        }
    }