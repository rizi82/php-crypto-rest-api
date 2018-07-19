<?php
 define('MAIN',realpath('../../'));
include_once MAIN.'/includes/functions.php';
include_once("Rest.inc.php");
	
	
class API extends REST {

	public $data = "";
	private $link = NULL;

	public function __construct(){
		parent::__construct();				// Init parent contructor
		$this->dbConnect();					// Initiate Database connection
	}
	
	/*
	 *  Database connection 
	*/
	private function dbConnect(){
	  // open connection
	 $this->link = openConnection(); 
	}
	
	/*
	 * Public method for access api.
	 * This method dynmically call the method based on the query string
	 *
	 */
	public function processApi(){
		$func = strtolower(trim(str_replace("/","",$_REQUEST['rquest'])));
		if((int)method_exists($this,$func) > 0)
			$this->$func();
		else
			$this->response('methods not exits, please contact to support',404);				
			// If the method not exist with in this class, response would be "Page not found".
	}
	
	

	// Public: this function show all active markets in system, filters by exchange and coin
	private function summary(){
		#echo "summary<br/>";
		#echo $_SERVER['QUERY_STRING'];
		// Cross validation if the request method is POST else it will return "Not Acceptable" status
		if($this->get_request_method() != "GET"){
			$this->response("Only GET Allowed.", 406);
		}
		$exc = isset($this->_request["exc"])?$this->_request["exc"]:"";
		$code = isset($this->_request["code"])?$this->_request["code"]:"";
		$exc		=	preg_replace('/[^\da-z]/i', '', $exc);	
		$code		=	preg_replace('/[^\da-z]/i', '', $code);	
		$query	=	"SELECT * FROM markets where status = 1 ";
		if ( !empty($exc) && ctype_alpha($exc)){
			$query	.=" and  exchange  = '".FixString($this->link, $exc)."'";
		}
		if ( !empty($code) && ctype_alpha($code)){
			$query	.=" and  code  = '".FixString($this->link, $code)."'";
		}
		
		$query	.=" order by code ASC ";
		list($rows, $total_no) = RunQuery($this->link, $query, true);
	    $result	=	array();
		if ( $total_no > 0 && !empty($rows)){
		    $result["result"] = "success";
			$result["total_no"] = $total_no;
		   foreach ( $rows as $row){
			    $result["data"][]	=	array("market_id" => $row["id"], "coin" => $row["coin"], "code" => $row["code"], "exchange" => $row["exchange"], "last_price" => $row["last_price"], "coin" => $row["coin"], "coin" => $row["coin"], "change" => 0, "24hhigh"=>"0.12000046","24hlow" => "0.12000045","24hvol" => "0.012","top_bid" => "0.12000045","top_ask"=>"0.15300000");
		   }
		    
			$this->response($this->json($result), 200); 
	   }
	   else{
			$result["result"] = "error";
			$result["message"] = "Please enter either BTC or LTC as the exchange filter";
			$this->response($this->json($result), 200); 
	   }
	}
	
	// Public: this function show all trades in system, filters by exchange and coin, it only show last 100
	private function trades(){
		#echo "summary<br/>";
		#echo $_SERVER['QUERY_STRING'];
		// Cross validation if the request method is POST else it will return "Not Acceptable" status
		if($this->get_request_method() != "GET"){
			$this->response("Only GET Allowed.", 406);
		}
		$exc = isset($this->_request["exc"])?$this->_request["exc"]:"";
		$code = isset($this->_request["code"])?$this->_request["code"]:"";
		$exc		=	preg_replace('/[^\da-z]/i', '', $exc);	
		$code		=	preg_replace('/[^\da-z]/i', '', $code);	
	
		$query	=	"SELECT * FROM orders where market_id > 0 ";
		if ( (!empty($exc) && ctype_alpha($exc)) && (!empty($code) && ctype_alpha($code)) ){
			$market_id  = 	getMarketId($code, $exc, $this->link);
				$query	.=" and  market_id  = '".FixString($this->link, $market_id)."'";
		}
		$query	.=" order by stime DESC ";
		
		list($rows, $total_no) = RunQuery($this->link, $query, true);
	    $result	=	array();
		if ( $total_no > 0 && !empty($rows)){
		    $result["result"] = "success";
			$result["total_no"] = $total_no;
		   foreach ( $rows as $row){
			    $result["data"][]	=	array("type" => $row["type"], "unix_time" => strtotime($row["stime"]), "utc_time" => date("d-m-Y H:i:s", strtotime($row["stime"])), "price" => $row["price"], "amount" => $row["amount"], "total" => $row["sub_total"], "status" => $row["status"]);
		   }
		    
			$this->response($this->json($result), 200); 
	   }
	   else{
			$result["result"] = "error";
			$result["message"] = "Please enter exchange and coin filters";
			$this->response($this->json($result), 200); 
	   }
	}
	
	//Priavte: auth the user against the priavte and public key
	private function __myAuthMethods($apiCall){
		// Cross validation if the request method is POST else it will return "Not Acceptable" status
		if($this->get_request_method() != "POST"){
			$this->response('Not Acceptable',406);
		}
		
		$exchange = isset($this->_request["exchange"])?$this->_request["exchange"]:"";
		$code = isset($this->_request["code"])?$this->_request["code"]:"";
		$time = isset($this->_request["time"])?$this->_request["time"]:"";
		$key = isset($this->_request["key"])?$this->_request["key"]:""; // public key
		$hash = isset($this->_request["hash"])?$this->_request["hash"]:"";
		$price = isset($this->_request["price"])?$this->_request["price"]:"";
		$amount = isset($this->_request["amount"])?$this->_request["amount"]:"";
		$type = isset($this->_request["type"])?$this->_request["type"]:"";
		
		$order_id = isset($this->_request["order_id"])?$this->_request["order_id"]:"";
  		
		 // Setup URL
 		$apiUrl = "http://127.0.0.1/crypto/anric/api/v1";
		if(!empty($code)){
		  $data['code'] = $code;
		}
		if(!empty($exchange)){
		 $data['exchange'] = $exchange;
		}
		if(!empty($order_id)){
		 $data['order_id'] = $order_id;
		}
		if(!empty($price)){
		 $data['price'] = $price;
		}
		if(!empty($amount)){
		 $data['amount'] = $amount;
		}
		if(!empty($type)){
		 $data['type'] = $type;
		}
		
		$data['time'] = $time;
    	$data['key'] = $key;
     
   		// Create a Hash at server and match to client Hash
   		$server_hash = hash_hmac("sha256", $apiUrl . $apiCall . "?" . http_build_query($data), PRIVATE_KEY);
		/*
		echo "<pre>";
		print_r($_POST);
		echo "server: ".$server_hash .' == '.  $hash ;
	*/
		// If both are not same then display error to end user
		if ( $server_hash  !=  $hash ){
			$this->response('Unauthorized: You are using wrong public or private key.', 401);
			exit();
		}
		else{
			// query to users table to get a userid
			$query	 = "SELECT id userId FROM users WHERE pub_key = '".FixString($this->link, $key)."' and status = 1 and email_verify = 1";
			$row = RunQuerySingle($this->link, $query);
			$userId = 0;
			 if(!empty($row)){
					$userId	 = $row["userId"];
				}
				return $userId;
		}
	
	}
	
	
	//Private: this function will place a order against a current user
	private function placeorder(){
		// Cross validation if the request method is POST else it will return "Not Acceptable" status
		if($this->get_request_method() != "POST"){
			$this->response('Not Acceptable',406);
		}
		$userId		= (int) $this->__myAuthMethods("/private/placeorder");
		if ($userId > 0 ) {
			
			$price = isset($this->_request["price"])?$this->_request["price"]:0;
			$amount = isset($this->_request["amount"])?$this->_request["amount"]:0;
			$type = isset($this->_request["type"])?$this->_request["type"]:"";
			$exchange = isset($this->_request["exchange"])?$this->_request["exchange"]:"";
			$code = isset($this->_request["code"])?$this->_request["code"]:"";
		
			$market_id	= 	getMarketId($code, $exchange, $this->link);
			$type		=	preg_replace('/[^\da-z]/i', '', $type);	
			$market 	= 	preg_replace(EXP_NUM_ONLY, '', $market_id);
			$amount 	= 	preg_replace(EXP_NUM_ONLY, '', $amount);
			$price 		= 	preg_replace(EXP_NUM_ONLY, '', $price);
			$sub_total	=	0;
			$sub_total	= 	number_format(($amount * $price), 8);
			$total 		= 	preg_replace(EXP_NUM_ONLY, '', $sub_total);
			
			$buyNetTotal 	=	0;
			$sellNetTotal	=	0;
			 if (strcasecmp($type, "sell") ==  0){
				$dbFee			=	number_format(($total * SELLER_FEE), 8); 
				$sellNetTotal	= 	number_format( ($total - $dbFee), 8 );
				$totalDBPrice	=	$sellNetTotal;
			 }
			if (strcasecmp($type, "buy") ==  0){
			  $dbFee			=	number_format(($total * BUYER_FEE), 8);
			  $buyNetTotal		= number_format( ($total + $dbFee), 8 );
			  $totalDBPrice	=	 $buyNetTotal;
			}
			
			if ( $total > 0  && $amount > 0  ){
			 	list($coinBalance, $exchangeBalance)	=   getUserMarketBalance($market, $userId, $this->link); // get exchange and coin price
			
			 // validations
			  $userBalanceCHK	=	TRUE; // if its true then lets user to place a order
			  if ( $amount  > $coinBalance  && (strcasecmp($type, "sell") ==  0)){
				 // amount is greter then $coin balance  
				$result["result"]	=	"faild"; // true
				$result["message"]	=	"SELL: There is not enough available balance to add this order."; // true		
				$userBalanceCHK		= 	FALSE;
				$this->response($this->json($result), 200); 
				exit();
			  }
			  if ( $buyNetTotal > $exchangeBalance  && (strcasecmp($type, "buy") ==  0)){
				 // amount is greter then $exchangeBalance balance  
				$result["result"]	=	"faild"; // true
				$result["message"]	=	"BUY: There is not enough available balance to add this order."; // true
				$userBalanceCHK		= 	FALSE;
				$this->response($this->json($result), 200); 
				exit();	
			  }
			  
			  if ( $userBalanceCHK ){
				  $dbTime	=	date("Y-m-d H:i:s");
				  $query	=	"INSERT into orders (market_id, type, stime, price, amount, sub_total, fee, total, created, created_by, status) values (".FixString($this->link, $market).", '".FixString($this->link, $type)."', 
				  '".$dbTime."', '".FixString($this->link, $price)."','".FixString($this->link, number_format($amount,8))."','".FixString($this->link, number_format($total,8))."',
				   '".FixString($this->link, number_format($dbFee,8))."', '".FixString($this->link, number_format($totalDBPrice,8))."', '".$dbTime."', ".FixString($this->link, $userId).", 'open')";
					if(mysqli_query($this->link, $query)){
					 $result["result"]	=	"success"; // true
					 $result["message"]	=	"The order has successfully gone to market."; // true
					 $orderId	=	mysqli_insert_id($this->link); // inserted order id
					 $result["data"]	=	array( "order_id" => $orderId, "type" => $type, "price" =>  number_format($price,8), "amount" => number_format($amount,8),
					 							 "total" => number_format($total,8), "fee" => number_format($dbFee,8), "net_total" => number_format($totalDBPrice,8),
												 "unix_time" => strtotime($dbTime), "utc_time" => date("d-m-Y H:i:s", strtotime($dbTime)) ); // true
					 // code to update user balance table
					 $newCoinBlance	=	0;
					 $newCoinBlance	=   number_format(($coinBalance - $amount),8);
					 $query	= "Update user_balances SET balance =  '".FixString($this->link, $newCoinBlance)."'
					 WHERE user_id = ".FixString($this->link, $userId)." AND market_id = ".FixString($this->link, $market)."";
					 if(mysqli_query($this->link, $query)){
						$this->response($this->json($result), 200);
					 }
				  }
			 }
		 }
	 }
			
	}
	
// Private this function cancel a open order for current user
  private function cancelorder(){
	 if($this->get_request_method() != "POST"){
			$this->response('Not Acceptable',406);
		}
		
		$userId		= (int) $this->__myAuthMethods("/private/cancelorder"); 
		if ( $userId > 0 ){
		 	$orderId = isset($this->_request["order_id"])?$this->_request["order_id"]:"";
				if ( $orderId > 0  ){
					// check the order is still open
				  $query	=	"SELECT * FROM  orders where id  = ".FixString($this->link, $orderId)." and created_by = ".FixString($this->link, $userId)." and status = 'open' ";
				   $rowData = RunQuerySingle($this->link, $query);
					  if(!empty($rowData)){ // yes order is open lets user to be cancel it 
						   $query	=	"Update orders SET updated = now(), updated_by = ".FixString($this->link, $userId).", status = 'cancel' where id  = ".FixString($this->link, $orderId)." ";
						if(mysqli_query($this->link, $query)){
						// update user balance info
						  list($coinBalance, $exchangeBalance)	=   getUserMarketBalance($rowData["market_id"], $userId, $this->link); // get exchange and coin price
						  
						   if ( strcasecmp($rowData["type"], "sell") ==  0){
						    $orderSubTotal	= $rowData["amount"];
						   }
						   else{
							    $orderSubTotal	= $rowData["sub_total"];
						   }
						   
						   $newCoinBlance	= ($coinBalance + $orderSubTotal);
						   
						   $query	= "Update user_balances SET balance =  '".FixString($this->link, $newCoinBlance)."'
							 WHERE user_id = ".FixString($this->link, $userId)." AND market_id = ".FixString($this->link, $rowData["market_id"])."";
							 if(!mysqli_query($this->link, $query)){
							   $result["result"]	=	"faild"; // true
							   $result["message"]	=	"Mysql Error (".$query.")"; // true	
							 }
					 		 $result["result"]		=	"success";
							 $result["message"]		=	"Your order has been cancel successfully.";
							 $this->response($this->json($result), 200); 
							}
							else{
							   $result["result"]	=	"faild"; // true
							   $result["message"]	=	"Mysql Error (".mysqli_errno($link).")"; // true	
							}
							
					  }
					  else{
					     $result["result"]	=	"faild"; // true
						 $result["rem_ordId"]	=	$orderId; // true
						 $result["message"]	=	"Sorry, your order has been processed."; // true	
						 $this->response($this->json($result), 200); 
					  }
				}
				
			
			
		}
		
		
  }
  
  //Private: this function return order detail for given order_id for current user
	private function getorder(){
		// Cross validation if the request method is POST else it will return "Not Acceptable" status
		if($this->get_request_method() != "POST"){
			$this->response('Not Acceptable',406);
		}
		$userId		= (int) $this->__myAuthMethods("/private/getorder");
		if ($userId > 0 ) {
			$orderId = isset($this->_request["order_id"])?$this->_request["order_id"]:"";
			$query	=	 "SELECT o.*, (select  CONCAT(markets.exchange , '/', markets.code)  FROM markets WHERE markets.id = o.market_id) market FROM orders 
			where o.id = ".FixString($this->link, $orderId)." and o.created_by = ".FixString($this->link, $userId)."  ";
			$row	=	RunQuerySingle($this->link, $query);
			$result	=	array();
			if(!empty($row)){
				$result["result"] = "success";
				$result["order"]	=	array("id" => $row["id"], "market" => $row["market"], "status" => $row["status"], "type" => $row["type"], "price" => $row["price"],
					 "amount" => $row["amount"], "sub_total" => $row["sub_total"],"fee" => $row["fee"],  "total" => $row["total"],
					 "unix_time" => strtotime($row["stime"]), "utc_time" => date("d-m-Y H:i:s", strtotime($row["stime"])) );
				$this->response($this->json($result), 200); 
			}
			else{
				$result["result"] = "error";
				$result["message"] = "There is no order against current user.";
				$this->response($this->json($result), 200); 
			}
		}
	}
	
	//Private: this function return all orders of current users
	private function orderlist(){
		// Cross validation if the request method is POST else it will return "Not Acceptable" status
		if($this->get_request_method() != "POST"){
			$this->response('Not Acceptable',406);
		}
		$userId		= (int) $this->__myAuthMethods("/private/orderlist");
		if ($userId > 0 ) {
			$query	=	 "SELECT o.*, (select  CONCAT(markets.exchange , '/', markets.code)  FROM markets WHERE markets.id = o.market_id) market FROM orders o 
			where o.created_by = ".FixString($this->link, $userId)." order by o.stime DESC limit 20 ";
			list($rows, $total_no) = RunQuery($this->link, $query, true);
			$result	=	array();
			if ( $total_no > 0 && !empty($rows)){
				$result["result"] = "success";
				$result["total_no"] = $total_no;
			   foreach ( $rows as $row){
				$result["order"][]	=	array("id" => $row["id"], "market" => $row["market"], "status" => $row["status"], "type" => $row["type"], "price" => $row["price"],
					 "amount" => $row["amount"], "sub_total" => $row["sub_total"],"fee" => $row["fee"],  "total" => $row["total"],
					 "unix_time" => strtotime($row["stime"]), "utc_time" => date("d-m-Y H:i:s", strtotime($row["stime"])) );
			   }
			   	$this->response($this->json($result), 200); 

			}
			else{
				$result["result"] = "error";
				$result["message"] = "There is no order against current user.";
				$this->response($this->json($result), 200); 
			}
		}
	}
	
	//
	// Private: this function fetch the all funds for given suer
	private function getfunds(){
	#   echo "mytrades<br/>";
	#	echo $_SERVER['QUERY_STRING'];
		// Cross validation if the request method is POST else it will return "Not Acceptable" status
		if($this->get_request_method() != "POST"){
			$this->response('Not Acceptable',406);
		}
		
		$userId		= (int) $this->__myAuthMethods("/private/getfunds");
		if ($userId > 0 ) {
		 $query	=	"SELECT m.*, IF(b.balance != '', sum(b.balance), 0) balance FROM markets m 
			LEFT JOIN user_balances b
			on
			b.market_id = m.id AND b.user_id =  ".FixString($this->link, $userId)." and m.status = 1
			group by m.code order by m.code ASC";
			
			list($rows, $total_no) = RunQuery($this->link, $query, true);
			$result	=	array();
			if ( $total_no > 0 && !empty($rows)){
				$result["result"] = "success";
				$result["total_no"] = $total_no;
			   foreach ( $rows as $row){
					$result["available_funds"][]	=	array("code" => $row["code"], "balance" => $row["balance"] );
			   }
				
				$this->response($this->json($result), 200); 
		   }
		   else{
				$result["result"] = "error";
				$result["message"] = "There is no trades against current user.";
				$this->response($this->json($result), 200); 
		   }
			
		}
	}
	
	// Private: this function show all trades in system, filters by exchange and coin, it only show last 100
	private function mytrades(){
		#echo "mytrades<br/>";
		#echo $_SERVER['QUERY_STRING'];
		// Cross validation if the request method is POST else it will return "Not Acceptable" status
		if($this->get_request_method() != "POST"){
			$this->response('Not Acceptable',406);
		}
		$exchange = isset($this->_request["exchange"])?$this->_request["exchange"]:"";
		$code = isset($this->_request["code"])?$this->_request["code"]:"";
		$exc		=	preg_replace('/[^\da-z]/i', '', $exc);	
		$code		=	preg_replace('/[^\da-z]/i', '', $code);	
		$userId		= (int) $this->__myAuthMethods("/private/mytrades");
		if ($userId > 0 ) {
		    $query	=	 "SELECT o.*, (select  CONCAT(markets.exchange , '/', markets.code)  FROM markets WHERE markets.id = o.market_id) market FROM trades o 
			where o.buyer_id = ".FixString($this->link, $userId)." OR o.seller_id = ".FixString($this->link, $userId)."  order by o.stime DESC LIMIT 20";
			list($rows, $total_no) = RunQuery($this->link, $query, true);
			$result	=	array();
			if ( $total_no > 0 && !empty($rows)){
				$result["result"] = "success";
				$result["total_no"] = $total_no;
			   foreach ( $rows as $row){
					$result["data"][]	=	array("order_id" => $row["id"], "type" => $row["type"], "market" =>  $row["market"], "price" => $row["price"],
					 "amount" => $row["amount"], "sub_total" => $row["sub_total"],"fee" => $row["fee"],  "total" => $row["total"],
					 "unix_time" => strtotime($row["stime"]), "utc_time" => date("d-m-Y H:i:s", strtotime($row["stime"])) );
			   }
				
				$this->response($this->json($result), 200); 
		   }
		   else{
				$result["result"] = "error";
				$result["message"] = "There is no trades against current users.";
				$this->response($this->json($result), 200); 
		   }
		}
		
	}
	
	private function deleteUser(){
		// Cross validation if the request method is DELETE else it will return "Not Acceptable" status
		if($this->get_request_method() != "DELETE"){
			$this->response('',406);
		}
		$id = (int)$this->_request['id'];
		if($id > 0){				
			mysql_query("DELETE FROM users WHERE user_id = $id");
			$success = array('status' => "Success", "msg" => "Successfully one record deleted.");
			$this->response($this->json($success),200);
		}else
			$this->response('',204);	// If no records "No Content" status
	}
	
	/*
	 *	Encode array into JSON
	*/
	private function json($data){
		if(is_array($data)){
			return json_encode($data);
		}
	}
}
	
	// Initiiate Library
	
	$api = new API;
	$api->processApi();
?>