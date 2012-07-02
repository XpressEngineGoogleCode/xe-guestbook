<?php
/**
 * @class  guestbookController
 * @author NHN (developers@xpressengine.com)
 * @brief  guestbook module Controller class
 **/

class guestbookController extends guestbook {

	/**
	 * @brief initialization
	 **/
	function init() {
	}

	/**
	 * @brief insert Guestbook Item (document) 
	 **/
	function procGuestbookInsertGuestbookItem(){
		$val = Context::gets('mid','user_name','email_address','password','content','parent_srl','guestbook_item_srl','page');
		// set
		$obj->module_srl = $this->module_srl;
		$obj->content = $val->content;

		// update
		if($val->guestbook_item_srl>0){
			$obj->email_address = $val->email_address;
			$obj->password = md5($val->password);

			$obj->guestbook_item_srl = $val->guestbook_item_srl;
			$output = executeQuery('guestbook.updateGuestbookItem', $obj);

		// insert
		}else{
			// if logined
			if(Context::get('is_logged')) {
				$logged_info = Context::get('logged_info');
				$obj->member_srl = $logged_info->member_srl;
				$obj->user_id = $logged_info->user_id;
				$obj->user_name = $logged_info->user_name;
				$obj->nick_name = $logged_info->nick_name;
				$obj->email_address = $logged_info->email_address;
				$obj->homepage = $logged_info->homepage;
			}else{
				if($val->user_name == "Username" || !$val->user_name) $val->user_name = "Anonymous";
				$obj->user_name = $val->user_name;
				$obj->nick_name = $val->user_name;
				$obj->email_address = $val->email_address;
				$obj->password = md5($val->password);
				$oGuestbookModel = &getModel('guestbook');		
			}

			$obj->guestbook_item_srl = getNextSequence();
			// reply
			if($val->parent_srl>0){
				$obj->parent_srl = $val->parent_srl;
				$obj->list_order = $obj->parent_srl * -1;
			}else{
				$obj->list_order = $obj->guestbook_item_srl * -1;
			}
			$output = executeQuery('guestbook.insertGuestbookItem', $obj);
		}
		if(!$output->toBool()) return $output;

		$obj->guestbook_count = 1;
		$this->add('page',$val->page?$val->page:1);

	    if(!in_array(Context::getRequestMethod(),array('XMLRPC','JSON'))) {
			$returnAct = Context::get("returnAct")?Context::get("returnAct"):"dispGuestbookContent";
			if($returnAct == "dispGuestbookContent")
				$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'mid', $this->module_info->mid, 'act', 'dispGuestbookContent');
			if($returnAct == "displayItemInfo"){
				$parent_srl = Context::get("parent_srl");
				$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'mid', $this->module_info->mid, 'act', 'displayItemInfo','guestbook_item_srl', $parent_srl);
			}
			header('location:'.$returnUrl);
			return;
		}

	}

	/**
	 * @brief Guestbook item delete
	 **/
	function procGuestbookDeleteGuestbookItem(){
		$guestbook_item_srl = Context::get('guestbook_item_srl');
        if(!$guestbook_item_srl) return new Object(-1,'msg_invalid_request');

        $logged_info = Context::get('logged_info');

		$output = $this->deleteGuestbookItem($guestbook_item_srl);
	}

	function deleteGuestbookItem($guestbook_item_srl){
		$oGuestbookModel = &getModel('guestbook');
		$output = $oGuestbookModel->getGuestbookItem($guestbook_item_srl);
		$oGuest = $output->data;

		if(!$oGuest) return new Object(-1,'msg_invalid_request');

		// delete children
		$pobj->parent_srl = $guestbook_item_srl;
		$output = executeQueryArray('guestbook.getGuestbookItem', $pobj);
		if($output->data){
			foreach($output->data as $k=>$v){
				$poutput = $this->deleteGuestbookItem($v->guestbook_item_srl);
				if(!$poutput->toBool()) return $poutput;
			}
		}
		
		$obj->guestbook_item_srl = $guestbook_item_srl;
		$output = executeQuery('guestbook.deleteGuestbookItem', $obj);
		if(!$output->toBool()) return $output;

		return $output;
	}

}
?>
