<?php
/**
 * @author Adam Zammit <adam@acspri.org.au>
 * @copyright 2020 ACSPRI <https://www.acspri.org.au>
 * @license GPL v3
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 */
    class OptOutAcceptAndMail extends PluginBase {

        protected $storage = 'DbStorage';
        static protected $description = 'Provide an alternative opt out screen that has customisable text and requires the user to click "opt out" for the opt out to occur. Also will send an email notifying of opt out status';
        static protected $name = 'OptOutAcceptAndMail';

        public function init() {
            $this->subscribe('beforeSurveySettings');
	    $this->subscribe('newSurveySettings');
            $this->subscribe('newDirectRequest'); //for new opt out screen
	    $this->subscribe('newUnsecureRequest','newDirectRequest'); //for new opt out screen
	    $this->subscribe('beforeTokenEmail');
        }


	protected $settings = array(
						'sInfo' => array (
								'type' => 'info',
								'label' => 'Please use the string NEWOPTOUTURL in your email templates for this function to operate',
								'help' => 'This plugin will replace NEWOPTOUTURL in email templates with the URL to this function',
						),

			'bUse' => array (
					'type' => 'select',
					'options' => array (
							0 => 'No',
							1 => 'Yes' 
					),
					'default' => 1,
					'label' => 'Use for every survey on this installation by default?',
					'help' => 'Overwritable in each Survey setting' 
			),
			'sText' => array (
					'type' => 'html',
					'default' => 'Are you sure you wish to opt out? We respect your privacy',
					'label' => 'The text to appear on the opt out page',
					'help' => 'Overwritable in each Survey setting' 
			),
			'bUseEmail' => array (
					'type' => 'select',
					'options' => array (
							0 => 'No',
							1 => 'Yes' 
					),
					'default' => 1,
					'label' => 'Send email to participant and adminsitrator notifying of opt out',
					'help' => 'Overwritable in each Survey setting' 
			),
			'sEmailSubject' => array (
					'type' => 'string',
					'default' => 'Opted out of survey',
					'label' => 'The text to appear in the email',
					'help' => 'Overwritable in each Survey setting' 
			),
	
			'sEmailText' => array (
					'type' => 'html',
					'default' => 'Are you sure you wish to opt out? We respect your privacy',
					'label' => 'The text to appear in the email',
					'help' => 'Overwritable in each Survey setting' 
			),
	
	);

//replace NEWOPTOUTURL with the opt out URL
 public function beforeTokenEmail()
	     {
		             $emailbody=$this->event->get("body");
			             if(empty($emailbody)) {
					                 return;
				     }
				$token = $this->event->get("token");
			     $survey = $this->event->get("survey");
				$url = Yii::app()->createAbsoluteUrl('plugins/direct', array('plugin' => "OptOutAcceptAndMail", 'surveyId' => $survey, "token" => $token["token"] ));
			     $this->event->set("body",str_replace("NEWOPTOUTURL",$url,$emailbody));
			     return;
    }



    public function newDirectRequest()
    {
	$oEvent = $this->getEvent();

        if ($oEvent->get('target') != $this->getName())
            return;

	
	if ((Yii::app()->request->getQuery('surveyId') == NULL) || (Yii::app()->request->getQuery('token') == NULL))
		return;


	$sSurveyId = Yii::app()->request->getQuery('surveyId');
	$sToken = Yii::app()->request->getQuery('token');

	if (!empty($sSurveyId)) { 
		$iSurveyId = intval($sSurveyId); 
        	$surveyidExists = Survey::model()->findByPk($iSurveyId);
	}

	if (!isset($surveyidExists)) {
		die("Survey does not exist");
	}
	if (!tableExists("{{tokens_$iSurveyId}}")) {
		die("No participant table for survey");
	}

	$aSurveyInfo = getSurveyInfo($iSurveyId);

	if (!(($this->get('bUse','Survey',$iSurveyId)==0)||(($this->get('bUse','Survey',$iSurveyId)==2) && ($this->get('bUse',null,null,$this->settings['bUse'])==0)))) { //if enabled for this survey
		//if accepted, send email then redirect to original opt out page to action opt out
		if (Yii::app()->request->getQuery('accept') != NULL) {
			if (!(($this->get('bUseEmail','Survey',$iSurveyId)==0)||(($this->get('bUseEmail','Survey',$iSurveyId)==2) && ($this->get('bUseEmail',null,null,$this->settings['bUseEmail'])==0)))) { //if enabled for this survey
				//send email to participant and CC survey admin
				$emailtext = ($this->get('bEmailTextOverwrite','Survey',$iSurveyId)==='1') ? $this->get('sEmailText','Survey',$iSurveyId) : $this->get('sEmailText',null,null,$this->settings['sEmailText']);
				$emailsubject = ($this->get('bEmailTextOverwrite','Survey',$iSurveyId)==='1') ? $this->get('sEmailSubject','Survey',$iSurveyId) : $this->get('sEmailSubject',null,null,$this->settings['sEmailSubject']);
				$token = Token::model($iSurveyId)->findByToken($sToken);
				SendEmailMessage($emailtext,$emailsubject,array($token->email,$aSurveyInfo['adminemail']),$aSurveyInfo['adminemail'],'LimeSurvey');

			}
			//redirect to opt out page
			Yii::app()->getController()->redirect(Yii::app()->createUrl('optout/removetokens', array('surveyid' => $iSurveyId, 'token' => $sToken )));
		} else {
			//display opt out page with link to acceptance of opt out
			$text = ($this->get('bTextOverwrite','Survey',$iSurveyId)==='1') ? $this->get('sText','Survey',$iSurveyId) : $this->get('sText',null,null,$this->settings['sText']);
			            $oSurvey = Survey::model()->findByPk($iSurveyId);
			            if(!$oSurvey) {
					                    $iSurveyId = null;
							                }
				                if($oSurvey) {
										                   $language = $oSurvey->language;
												                        App()->setLanguage($language);
									                $renderData['aSurveyInfo'] = getSurveyInfo($iSurveyId,$language);
									                Template::model()->getInstance(null, $iSurveyId);
											            }

			$renderData['aSurveyInfo']['active'] = 'Y'; // Didn't show the default warning
		        $renderData['aSurveyInfo']['options']['ajaxmode'] = "off"; // Try to disable ajax mode
		        $renderData['aSurveyInfo']['include_content'] = 'optin';
		        $renderData['aSurveyInfo']['optin_message'] = $text . "<br/>" . "<a href='/" . Yii::app()->request->getPathInfo() ."/accept/accept' class='btn btn-default'>Opt out</a>";
		        Yii::app()->twigRenderer->renderTemplateFromFile('layout_global.twig', $renderData,false);
		        Yii::app()->end();

		}
	} else {
		die("Unavailable");
	}
    }


  /**
    * Add setting on survey level: send hook only for certain surveys / url setting per survey / auth code per survey / send user token / send question response
    */
    public function beforeSurveySettings()
    {
      $oEvent = $this->event;
      $oEvent->set("surveysettings.{$this->id}", array(
				'name' => get_class ( $this ),
				'settings' => array (
						'sInfo' => array (
								'type' => 'info',
								'label' => 'Please use the string NEWOPTOUTURL in your email templates for this function to operate',
								'help' => 'This plugin will replace NEWOPTOUTURL in email templates with the URL to this function',
						),
					'bUse' => array (
								'type' => 'select',
								'label' => 'Enable the opt out page for this survey?',
								'options' => array (
										0 => 'No',
										1 => 'Yes',
										2 => 'Use site settings (default)' 
								),
								'default' => 2,
								'help' => 'Leave default to use global setting',
								'current' => $this->get ( 'bUse', 'Survey', $oEvent->get ( 'survey' ) ) 
						),
						'bTextOverwrite' => array (
								'type' => 'select',
								'label' => 'Overwrite the global opt out text to appear on the page with the text below?',
								'options' => array (
										0 => 'No',
										1 => 'Yes' 
								),
								'default' => 0,
								'help' => 'Set to Yes if you want to use specific text for this survey',
								'current' => $this->get ( 'bTextOverwrite', 'Survey', $oEvent->get ( 'survey' ) ) 
						),
						'sText' => array (
								'type' => 'html',
								'label' => 'The text to appear on the opt out page',
								'help' => 'Leave blank to use global setting',
								'current' => $this->get ( 'sText', 'Survey', $oEvent->get ( 'survey' ) ) 
						),
						'bUseEmail' => array (
								'type' => 'select',
								'label' => 'Send email to participant and adminsitrator notifying of opt out',
								'options' => array (
										0 => 'No',
										1 => 'Yes',
										2 => 'Use site settings (default)' 
								),
								'default' => 2,
								'help' => 'Leave default to use global setting',
								'current' => $this->get ( 'bUseEmail', 'Survey', $oEvent->get ( 'survey' ) ) 
						),
						'bEmailTextOverwrite' => array (
								'type' => 'select',
								'label' => 'Overwrite the global opt out text to appear in the email with the text below?',
								'options' => array (
										0 => 'No',
										1 => 'Yes' 
								),
								'default' => 0,
								'help' => 'Set to Yes if you want to use specific email text for this survey',
								'current' => $this->get ( 'bTextOverwrite', 'Survey', $oEvent->get ( 'survey' ) ) 
						),
						'sEmailSubject' => array (
								'type' => 'string',
								'label' => 'The subject of the email the email',
								'help' => 'Leave blank to use global setting',
								'current' => $this->get ( 'sEmailSubject', 'Survey', $oEvent->get ( 'survey' ) ) 
						),
						'sEmailText' => array (
								'type' => 'html',
								'label' => 'The text to appear in the email',
								'help' => 'Leave blank to use global setting',
								'current' => $this->get ( 'sEmailText', 'Survey', $oEvent->get ( 'survey' ) ) 
						),
	
				) 
		));
  }

    /**
      * Save the settings
      */
      public function newSurveySettings()
    {
        $event = $this->event;
        foreach ($event->get('settings') as $name => $value)
        {
            /* In order use survey setting, if not set, use global, if not set use default */
            $default=$event->get($name,null,null,isset($this->settings[$name]['default'])?$this->settings[$name]['default']:NULL);
            $this->set($name, $value, 'Survey', $event->get('survey'),$default);
        }
    }


}
