<?php

namespace MediaWiki\Extension\ExtendedSignup;

use MediaWiki\Auth\AuthenticationResponse;
use MediaWiki\Auth\AuthManager;
use MediaWiki\Preferences\Hook\GetPreferencesHook;
use MediaWiki\User\Hook\LocalUserCreatedHook;
use MediaWiki\User\UserIdentity;
use MediaWiki\User\UserOptionsManager;

/**
 * Hook handler for ExtendedSignup extension.
 */
class HookHandler implements
	GetPreferencesHook,
	LocalUserCreatedHook
{

	/** @var UserOptionsManager */
	private $userOptionsManager;

	/** @var AuthManager */
	private $authManager;

	/**
	 * @param UserOptionsManager $userOptionsManager
	 * @param AuthManager $authManager
	 */
	public function __construct(
		UserOptionsManager $userOptionsManager,
		AuthManager $authManager
	) {
		$this->userOptionsManager = $userOptionsManager;
		$this->authManager = $authManager;
	}

	/**
	 * @inheritDoc
	 */
	public function onLocalUserCreated( UserIdentity $user, $autocreated ) {
		$requests = $this->authManager->getAuthenticationRequests( AuthManager::ACTION_CREATE );
		foreach ( $requests as $request ) {
			if ( property_exists( $request, 'extendedsignup_phone' ) && $request->extendedsignup_phone !== null ) {
				$this->userOptionsManager->setOption(
					$user,
					'extendedsignup-phone',
					$request->extendedsignup_phone
				);
				$this->userOptionsManager->saveOptions( $user );
				break;
			}
		}
	}

	/**
	 * @inheritDoc
	 */
	public function onAuthChangeFormFields( array $requests, array $fieldInfo, array &$formDescriptor, $action ) {
		if ( $action !== AuthManager::ACTION_CREATE ) {
			return;
		}

		if ( isset( $formDescriptor['realname'] ) ) {
			$formDescriptor['realname']['required'] = true;
		}

		$formDescriptor['extendedsignup_phone'] = [
			'type' => 'text',
			'label-message' => 'extendedsignup-field-phone',
			'help-message' => 'extendedsignup-field-phone-help',
			'section' => 'personal/info',
			'priority' => 100,
		];
	}

	/**
	 * @inheritDoc
	 */
	public function onGetPreferences( UserIdentity $user, &$preferences ) {
		$preferences['extendedsignup-phone'] = [
			'type' => 'text',
			'label-message' => 'extendedsignup-field-phone',
			'section' => 'personal/email',
		];
	}
}
