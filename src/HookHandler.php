<?php

namespace MediaWiki\Extension\ExtendedSignup;

use MediaWiki\Auth\AuthManager;
use MediaWiki\User\UserIdentity;
use MediaWiki\User\UserOptionsManager;
use MediaWiki\HTMLForm\HTMLForm;

class HookHandler
{
    /** @var UserOptionsManager */
    private $userOptionsManager;

    /** @var AuthManager */
    private $authManager;

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
    public function onLocalUserCreated( UserIdentity $user, $autocreated ): void {
        $requests = $this->authManager->getAuthenticationRequests( AuthManager::ACTION_CREATE );
        foreach ( $requests as $request ) {
            if ( property_exists( $request, 'extendedsignup_phone' ) && $request->extendedsignup_phone !== null ) {
                $this->userOptionsManager->setOption(
                    $user,
                    'extendedsignup-phone',
                    $this->sanitizePhone( $request->extendedsignup_phone )
                );
                $this->userOptionsManager->saveOptions( $user );
                break;
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function onAuthChangeFormFields(
        array $requests,
        array $fieldInfo,
        array &$formDescriptor,
              $action
    ): void {
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
            'maxlength' => 20,
            'filter-callback' => [ $this, 'sanitizePhone' ],
            'validation-callback' => [ $this, 'validatePhone' ],
        ];
    }

    /**
     * @inheritDoc
     */
    public function onGetPreferences( UserIdentity $user, array &$preferences ): void {
        $preferences['extendedsignup-phone'] = [
            'type' => 'text',
            'label-message' => 'extendedsignup-field-phone',
            'section' => 'personal/email',
            'maxlength' => 20,
            'filter-callback' => [ $this, 'sanitizePhone' ],
            'validation-callback' => [ $this, 'validatePhone' ],
        ];
    }

    /**
     * Sanitizes a phone number.
     *
     * @param string|null $value
     * @return string
     */
    public function sanitizePhone( $value ): string {
        $value = trim( (string)$value );
        return substr( preg_replace( '/[^\d+\-\(\) ]/', '', $value ), 0, 20 );
    }

    /**
     * Validates a phone number.
     *
     * @param string $value
     * @param array $alldata
     * @param HTMLForm $form
     * @return bool|string|\Message
     */
    public function validatePhone( $value, $alldata, $form ) {
        if ( $value === '' ) {
            return true;
        }

        if ( preg_match( '/[^\d+\-\(\) ]/', $value ) ) {
            return $form->msg( 'extendedsignup-error-phone-invalid' );
        }

        return true;
    }
}
