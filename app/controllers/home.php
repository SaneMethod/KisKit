<?php
/**
 * Copyright (c) Christopher Keefer, 2013. All Rights Reserved.
 *
 * Default route, controller for serving relevant pages.
 */

use sanemethod\kiskit\system\core\Controller;
use sanemethod\kiskit\system\core\ExceptHandler;

class HomeController extends Controller {

    const VIEW_LOC = 'home';

    /**
     * Load the initial view and all related data.
     */
    public function index()
    {
        $invite = $this->model('invitecodes');
        $interests = $this->model('teaminterests');

        //$this->logger->log(ExceptHandler::ALERT, 'Stick {that} in your {pipe} and {smoke} it!',
        //    array('that'=>'blarney', 'pipe'=>'tube', 'smoke'=>'banana'));

        //throw new Exception('Coffee required.', 418);

        $this->view('index', ['interests'=>$interests->select(),
            'inviteCode'=>$invite->selectOne(['where'=>['user_id' => 0]])]);
        $this->view('footer');
    }

    public function restEx_GET()
    {
        $this->json(array('success'=>true, 'message'=>'Retrieved via GET.', 'request'=>$this->request));
    }

    public function restEx_POST()
    {
        $this->json(array('success'=>true, 'message'=>'Retrieved via POST.', 'request'=>$this->request));
    }

    /**
     * Store the member info provided in POST, and send a confirmation email.
     */
    public function storeMember()
    {
        $memberInfo = json_decode($_POST['member'], TRUE);
        $interestInfo = $memberInfo['interests'];
        unset($memberInfo['interests']);

        $member = $this->model('member');
        $memberID = $member->insert($memberInfo);
        if ($memberID == null){
            $this->json(array('success'=>false));
            return;
        }
        // Set interests for this member
        foreach($interestInfo as &$interest)
        {
            $interest = array('user_id'=>$memberID, 'interest_id'=>$interest);
        }
        $interests = $this->model('memberteaminterests');
        $interests->insertMany($interestInfo);

        // Send confirmation email and echo results as json
        $this->json(array('memberID'=>$memberID,
            'success'=>$this->sendConfirmMail($member->selectOne('user_id', $memberID))));
    }

    /**
     * Verify the confirmation code stored in the url.
     */
    public function confirm()
    {
        $id =& $_GET['id'];
        $confirmCode =& $_GET['code'];
        $member = $this->model('member');
        if (isset($id) && isset($confirmCode) && $member->processConfirmation($id, $confirmCode))
        {
            $this->view('confirm', array('confirmed'=>TRUE, 'member'=>$member->selectOne('user_id', $id)));
        }
        else
        {
            $this->view('confirm', array('confirmed'=>FALSE));
        }
        $this->view('footer');
    }

    /**
     * Perform 'login' - just echo result of password confirmation for now
     */
    public function login()
    {
        $email =& $_POST['email_address'];
        $password =& $_POST['password'];
        $member = $this->model('member');
        $memberInfo = $member->selectOne(['fields'=>'email_address', 'where'=>['email_address'=>$email]]);

        $this->json(array('success'=>$member->comparePassword('email_address', $email, $password),
            'first_name'=>$memberInfo['first_name']));
    }

    /**
     * Send the confirmation email.
     * @param $memberInfo
     */
    protected function sendConfirmMail($memberInfo)
    {
        // Get html output of view of email template
        ob_start();
        $confirmLink = BASE_URL . "index.php/home/confirm?id=".$memberInfo['user_id']
            ."&code=".$memberInfo['confirmation_code'];
        $this->view('emailTemplate', array('firstName'=>$memberInfo['first_name'],
            'lastName'=>$memberInfo['last_name'], 'confirmLink'=>$confirmLink));
        $message = ob_get_clean();
        // Send email
        $email = $this->helper('email');
        return $email->from('membership@jointhecrowd.com')->to(array("{$memberInfo['email_address']}"))
            ->subject('Confirmation Email')->message("{$message}")->send();
    }
}
