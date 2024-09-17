<?php

use AcyMailing\Classes\MailClass;
use AcyMailing\Libraries\acymPlugin;

class plgAcymAcymailer extends acymPlugin
{
    const SENDING_METHOD_ID = 'acymailer';
    const SENDING_METHOD_NAME = 'AcyMailing sending service';
    const SENDING_METHOD_API_URL = 'https://api.acymailer.com/';
    const CREDITS_RELOAD_DELAY = 900;
    const TRANSLATIONS = [
        0 => 'ACYM_ERROR_OCCURRED',
        1 => 'ACYM_PLEASE_INSTALL_LATEST_VERSION',
        2 => 'ACYM_ERROR_OCCURRED_WHEN_TRYING_TO_SEND_THE_EMAIL',
        3 => 'ACYM_DOMAIN_NOT_ATTACHED_TO_THE_SITE',
        4 => 'ACYM_NOT_ALLOWED_TO_DELETE_THIS_DOMAIN',
        5 => 'ACYM_DOMAIN_DOES_NOT_EXIST',
        6 => 'ACYM_DOMAIN_STATUS_SUCCESSFULLY_OBTAINED',
        7 => 'ACYM_AN_ERROR_RELATED_TO_THE_REMAINING_CREDITS_OCCURRED',
        8 => 'ACYM_LICENSE_EXPIRED',
        9 => 'ACYM_IDENTITY_CREATED',
        10 => 'ACYM_DOMAIN_ALREADY_EXISTS_FOR_THIS_WEBSITE',
        11 => 'ACYM_DOMAIN_ALREADY_EXISTS_AND_DOES_NOT_BELONG_TO_YOU',
        12 => 'ACYM_NO_DOMAINS_TO_CHECK',
        13 => 'ACYM_DOMAINS_VERIFIED',
        14 => 'ACYM_ERROR_WHILE_CHECKING_DOMAINS',
        15 => 'ACYM_BLOCKED_LICENSE',
        16 => 'ACYM_MISSING_EMAIL',
        17 => 'ACYM_MISSING_ARGUMENTS',
        18 => 'ACYM_NO_CREDITS_LEFT',
        19 => 'ACYM_MISSING_API_KEY',
        20 => 'ACYM_DOMAIN_NOT_FOUND',
        21 => 'ACYM_LICENSE_NOT_FOUND',
        22 => 'ACYM_EMAIL_SENT',
        23 => 'ACYM_INVALID_API_KEY',
        24 => 'ACYM_LICENSE_BLOCKED',
    ];
    private $errorCodes = [0, 1, 2, 3, 4, 5, 7, 8, 10, 11, 12, 14, 15, 16, 17, 18, 19, 20, 21, 23, 24];

    public function __construct()
    {
        parent::__construct();
        $this->pluginDescription->name = self::SENDING_METHOD_NAME;
    }

    protected function callApiSendingMethod($url, $data = [], $headers = [], $type = 'GET', $authentication = [], $dataDecoded = false)
    {
        if (strpos($url, self::SENDING_METHOD_API_URL) === false) {
            $url = self::SENDING_METHOD_API_URL.'api/'.$url;
        }

        $defaultHeaders = [
            'accept' => 'application/json',
            'content-type' => 'application/json',
            'API-KEY' => $this->config->get(self::SENDING_METHOD_ID.'_apikey', ''),
            'Version' => $this->config->get('version'),
        ];

        foreach ($defaultHeaders as $key => $value) {
            if (!isset($headers[$key])) {
                $headers[$key] = $value;
            }
        }

        $result = parent::callApiSendingMethod($url, $data, $headers, $type, $authentication, $dataDecoded);
        if (!empty($result['error_curl'])) {
            $result['message'] = $result['error_curl'];
            $this->errors[] = $result['error_curl'];
            $this->errorCallback();
        } else {
            $result['message'] = $this->translate($result);
        }

        return $result;
    }

    public function onAcymGetSendingMethods(&$data, $isMailer = false)
    {
        $data['sendingMethods'][self::SENDING_METHOD_ID] = [
            'name' => $this->pluginDescription->name,
            'image' => ACYM_IMAGES.'mailers/'.self::SENDING_METHOD_ID.'.png',
            'recommended' => true,
            'image_class' => '',
        ];
    }

    public function onAcymAttachedLicenseOption()
    {
        $licenseKey = $this->config->get('license_key');
        $acyMailerKey = $this->config->get('acymailer_apikey');
        $mailerMethod = $this->config->get('mailer_method');

        if (empty($licenseKey) || empty($acyMailerKey) || $mailerMethod === self::SENDING_METHOD_ID) {
            return;
        }
        ?>
        <div class="grid-x grid-margin-x margin-top-1" id="activate_ass_method_notice">
            <label class="cell medium-7 acym_vcenter">
                <i class="acymicon-info-circle" id="acymailer_info_icon"></i>
                <?php echo acym_translation('ACYM_ACTIVATE_ACYMAILER'); ?>
            </label>
            <button type="button"
                    id="acym__configuration__activate__acymailer"
                    class="cell shrink button">
                <?php echo acym_translation('ACYM_SEE_THE_SENDING_METHOD'); ?>
            </button>
        </div>
        <?php
    }

    public function onAcymGetSendingMethodsHtmlSetting(&$data)
    {
        $licenseKey = $this->config->get('license_key');
        $acyMailerKey = $this->config->get('acymailer_apikey');
        $domains = $this->config->get(self::SENDING_METHOD_ID.'_domains', []);
        $domainsWaiting = [];
        if (!empty($domains)) {
            $domains = json_decode($domains, true);
            $domainsWaiting = array_filter($domains, function ($domain) {
                return $domain['status'] !== 'SUCCESS';
            });
        }

        $unverifiedDomains = $this->getDomainsUnVerified();

        ob_start();
        ?>
        <div class="send_settings cell grid-x acym_vcenter" id="<?php echo self::SENDING_METHOD_ID; ?>_settings">
            <div class="cell grid-x acym__sending__methods__one__settings">
                <?php if (!acym_level(ACYM_ESSENTIAL) && empty($data['step'])) { ?>
                    <div class="acym_vcenter acym__config__acymailer__warning margin-top-1 cell grid-x ">
                        <label for="acym__configuration__license-key" class="cell large-1 medium-3">
                            <?php echo acym_translation('ACYM_YOUR_LICENSE_KEY'); ?>
                        </label>
                        <input type="text" name="config[license_key]" id="acym__configuration__license-key" class="medium-4 cell" value="<?php echo $licenseKey; ?>">
                        <button type="button"
                                id="acym__configuration__button__license"
                                class="cell shrink button margin-left-1"
                                data-acym-linked="<?php echo empty($licenseKey) ? 0 : 1; ?>">
                            <?php echo acym_translation(empty($licenseKey) ? 'ACYM_ATTACH_MY_LICENSE' : 'ACYM_UNLINK_MY_LICENSE'); ?>
                        </button>
                    </div>
                <?php }
                if (empty($licenseKey)) { ?>
                    <div class="cell acym_vcenter acym__config__acymailer__warning margin-top-1 <?php echo empty($data['step']) ? 'large-7' : '' ?>">
                        <i class="acymicon-exclamation-triangle acym__color__orange"></i>
                        <span class="margin-right-1">
							<?php
                            $pricingPage = ACYM_ACYMAILING_WEBSITE.'pricing?utm_source=acymailing_plugin&utm_medium=sendingmethod&utm_campaign=purchase';
                            echo acym_translation(acym_level(ACYM_ESSENTIAL) ? 'ACYM_NO_LICENSE' : 'ACYM_NO_LICENSE_STARTER');
                            ?>
							<a target="_blank" href="<?php echo $pricingPage; ?>"><?php echo acym_translation('ACYM_GET_A_LICENSE'); ?></a>
						</span>
                    </div>
                <?php } elseif (empty($acyMailerKey)) { ?>
                    <div class="cell acym_vcenter acym__config__acymailer__warning margin-top-1 <?php echo empty($data['step']) ? 'large-7' : '' ?>">
                        <i class="acymicon-exclamation-triangle acym__color__orange"></i>
                        <span>
							<?php
                            $subscriptionsPage = ACYM_ACYMAILING_WEBSITE.'account/subscriptions?utm_source=acymailing_plugin&utm_medium=sendingmethod&utm_campaign=purchase';
                            echo acym_translation('ACYM_API_KEY_NOT_ALLOWING_METHOD');
                            ?>
							<a target="_blank" href="<?php echo $subscriptionsPage; ?>"><?php echo acym_translation('ACYM_SWITCH_MY_LICENSE'); ?></a>
						</span>
                    </div>
                <?php } else { ?>
                    <div class="cell grid-x">
                        <?php
                        if (!empty($domainsWaiting)) { ?>
                            <div class="cell acym_vcenter acym__config__acymailer__warning margin-top-1 <?php echo empty($data['step']) ? 'large-7' : '' ?>">
                                <i class="acymicon-access_time acym__color__orange"></i>
                                <span><?php echo acym_translation('ACYM_DOMAINS_WAITING_VALIDATION_ADD_CNAME'); ?></span>
                                <a href="<?php echo ACYM_DOCUMENTATION; ?>external-sending-method/acymailing-sending-service#how-to-add-the-dns-entries-on-my-server"
                                   target="_blank">
                                    <?php echo acym_translation('ACYM_CHECK_THIS_TUTORIAL'); ?>
                                </a>
                            </div>
                            <div class="cell <?php echo empty($data['step']) ? 'large-2' : '' ?> margin-top-1">
                                <button id="acym__config__acymailer__update-domain-status"
                                        type="button"
                                        class="button button-secondary float-right"
                                        sending-method-id="<?php echo self::SENDING_METHOD_ID; ?>">
                                    <i class="acymicon-autorenew"></i><?php echo acym_translation('ACYM_UPDATE_DOMAIN_STATUS'); ?>
                                </button>
                            </div>
                        <?php } ?>
                    </div>
                    <div class="grid-x acym__listing cell <?php echo empty($data['step']) ? 'large-9' : '' ?>">
                        <div class="grid-x cell acym__listing__header">
                            <div class="grid-x medium-auto small-11 cell acym__listing__header__title__container">
                                <div class="medium-auto cell acym__listing__header__title">
                                    <?php echo acym_translation('ACYM_DOMAIN_NAME'); ?>
                                </div>
                                <div class="medium-2 cell acym__listing__header__title text-center">
                                    <?php echo acym_translation('ACYM_STATUS'); ?>
                                </div>
                                <div class="medium-2 cell acym__listing__header__title text-center">
                                    <?php echo acym_translation('ACYM_BOUNCE_RATE').acym_info('ACYM_BOUNCE_RATE_DESC'); ?>
                                </div>
                                <div class="medium-2 cell acym__listing__header__title text-center">
                                    <?php echo acym_translation('ACYM_COMPLAINT_RATE').acym_info('ACYM_COMPLAINT_RATE_DESC'); ?>
                                </div>
                                <div class="medium-2 cell acym__listing__header__title text-center">
                                    <?php echo acym_translation('ACYM_ACTIONS'); ?>
                                </div>
                            </div>
                        </div>
                        <?php if (empty($domains)) { ?>
                            <div class="grid-x cell margin-top-1 align-center">
                                <?php echo acym_translation('ACYM_NO_DOMAINS_YET'); ?>
                            </div>
                        <?php } else { ?>
                            <?php foreach ($domains as $domain) { ?>
                                <div acym-data-domain="<?php echo $domain['domain'] ?>" class="grid-x cell acym__listing__row">
                                    <div class="grid-x medium-auto cell acym__listing__title__container">
                                        <?php echo $domain['domain']; ?>
                                    </div>
                                    <div class="grid-x medium-2 cell acym__listing__title__container align-center">
                                        <?php
                                        switch ($domain['status']) {
                                            case 'SUCCESS':
                                                $iconClass = 'acymicon-check-circle acym__color__green';
                                                $tooltipText = acym_translation('ACYM_VALIDATED');
                                                break;
                                            case 'FAILED':
                                                $iconClass = 'acymicon-remove acym__color__red notValidated';
                                                $tooltipText = acym_translation('ACYM_APPROVAL_FAILED');
                                                break;
                                            default:
                                                $iconClass = 'acymicon-access_time acym__color__orange notValidated';
                                                $tooltipText = acym_translation('ACYM_PENDING');
                                        }
                                        echo acym_tooltip('<i class="acym__config__acymailer__status__icon '.$iconClass.'"></i>', $tooltipText);
                                        ?>
                                    </div>
                                    <div class="grid-x medium-2 cell acym__listing__title__container align-center">
                                        <?php echo empty($domain['bounce_rate']) ? '-' : round($domain['bounce_rate'], 2).'%'; ?>
                                    </div>
                                    <div class="grid-x medium-2 cell acym__listing__title__container align-center">
                                        <?php echo empty($domain['complaint_rate']) ? '-' : round($domain['complaint_rate'], 2).'%'; ?>
                                    </div>
                                    <div class="grid-x medium-2 cell acym__listing__title__container align-center">
                                        <?php ob_start(); ?>
                                        <div class="cell grid-x acym__config__acymailer__cname__modal">
                                            <h6 class="cell text-center margin-top-2 margin-bottom-1"><?php echo acym_translation('ACYM_DNS_ENTRIES'); ?></h6>
                                            <div class="grid-x cell align-center margin-top-1 margin-bottom-2 acym_vcenter">
                                                <p class="cell shrink"><?php echo acym_translation('ACYM_DO_NOT_KNOW_HOW_DO_IT'); ?> </p>
                                                <a href="<?php echo ACYM_DOCUMENTATION; ?>external-sending-method/acymailing-sending-service#how-to-add-the-dns-entries-on-my-server"
                                                   class="cell shrink acym__config__acymailer__cname__modal__link"
                                                   target="_blank">
                                                    <?php echo acym_translation('ACYM_STEP_BY_STEP_GUIDE'); ?>
                                                </a>
                                            </div>
                                            <div class="grid-x acym__listing cell">
                                                <div class="grid-x acym__listing cell">
                                                    <div class="grid-x cell acym__listing__header">
                                                        <div class="medium-6 cell acym__listing__header__title">
                                                            <?php echo acym_translation('ACYM_NAME'); ?>
                                                        </div>
                                                        <div class="medium-6 cell acym__listing__header__title">
                                                            <?php echo acym_translation('ACYM_VALUE'); ?>
                                                        </div>
                                                    </div>
                                                    <?php foreach ($domain['CnameRecords'] as $cnameRecord) { ?>
                                                        <div class="grid-x cell acym__listing__row">
                                                            <div class="grid-x medium-6 cell acym__listing__title__container cname-name">
                                                                <?php echo $cnameRecord['name']; ?>
                                                            </div>
                                                            <div class="grid-x medium-6 cell acym__listing__title__container cname-value">
                                                                <?php echo $cnameRecord['value']; ?>
                                                            </div>
                                                        </div>
                                                    <?php } ?>
                                                </div>
                                            </div>
                                        </div>
                                        <?php
                                        $modalContent = ob_get_clean();
                                        $buttonModal = acym_tooltip(
                                            '<i class="acymicon-cogs acym__config__acymailer__domain--settings cursor-pointer acym__color__blue" acym-data-domain="'.$domain['domain'].'"></i>',
                                            acym_translation('ACYM_SHOW_DNS_SETTINGS')
                                        );
                                        echo acym_modal(
                                            $buttonModal,
                                            $modalContent,
                                            null,
                                            '',
                                            '',
                                            true,
                                            true,
                                            'acym__config__acymailer__cname__modal__container'
                                        );

                                        echo acym_tooltip(
                                            '<i class="acymicon-delete acym__config__acymailer__domain--delete cursor-pointer" acym-data-domain="'.$domain['domain'].'"></i>',
                                            acym_translation('ACYM_DELETE')
                                        );
                                        ?>
                                    </div>
                                </div>
                            <?php } ?>
                        <?php } ?>
                    </div>
                    <div class="cell grid-x acym_vcenter acym__sending__methods__one__settings padding-top-1">
                        <div class="cell grid-x">
                            <div>
                                <input id="<?php echo self::SENDING_METHOD_ID; ?>_domain"
                                       class="cell medium-6 large-4 xlarge-3"
                                       type="text"
                                       autocomplete="off"
                                       value="">
                                <span id="<?php echo self::SENDING_METHOD_ID; ?>_domain_error" class="medium-6 large-4 xlarge-3"></span>
                                <?php if (!empty($unverifiedDomains)) { ?>
                                    <span id="acym__acymailer__unverifiedDomains">
									<?php foreach ($unverifiedDomains as $oneDomain) { ?>
                                        <span class="acym__acymailer__oneSuggestion"><?php echo $oneDomain; ?></span>
                                    <?php } ?>
									</span>
                                <?php } ?>
                            </div>
                            <div id="acym__configuration__sending__method_addDomain_submit" class="cell grid-x medium-6 large-8 padding-left-1 acym_vcenter">
                                <button type="button"
                                        id="acym__configuration__sending__method-addDomain"
                                        class="cell shrink button button-secondary">
                                    <?php echo acym_translation('ACYM_ADD_A_DOMAIN'); ?>
                                </button>
                                <i class="margin-left-1 acym_vcenter acymicon-circle-o-notch acymicon-spin is-hidden" id="acym__configuration__sending__method_add_domain-wait"></i>
                                <div class="cell shrink grid-x acym_vcenter" id="acym__configuration__acymailer__add__error">
                                    <i class="acymicon-close acym__color__red cell shrink"></i>
                                    <span class="cell shrink" id="acym__configuration__acymailer__add__error__message"></span>
                                </div>
                            </div>
                            <div class="cell grid-x medium-6 large-8 acym_vcenter margin-top-1">
                                <p class="cell shrink"><?php echo acym_translation('ACYM_DO_NOT_KNOW_HOW_DO_IT'); ?> </p>
                                <a href="<?php echo ACYM_DOCUMENTATION; ?>external-sending-method/acymailing-sending-service#configure-the-sending-method"
                                   class="cell shrink acym__config__acymailer__cname__modal__link margin-left-1"
                                   target="_blank">
                                    <?php echo acym_translation('ACYM_STEP_BY_STEP_GUIDE'); ?>
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php if (!$this->isLogFileEmpty()) { ?>
                        <div class="cell grid-x margin-top-1 acym__sending__methods__log">
                            <?php
                            echo acym_modal(
                                acym_translation('ACYM_REPORT_SEE'),
                                '',
                                null,
                                '',
                                [
                                    'class' => 'button',
                                    'data-ajax' => 'true',
                                    'data-iframe' => '&ctrl=configuration&task=seeLogs&filename='.$this->logFilename,
                                ]
                            );
                            ?>
                        </div>
                    <?php } ?>
                <?php } ?>
            </div>
        </div>
        <?php
        $data['sendingMethodsHtmlSettings'][self::SENDING_METHOD_ID] = ob_get_clean();
    }

    public function onAcymTestCredentialSendingMethod($sendingMethod, $credentials)
    {
        if ($sendingMethod !== self::SENDING_METHOD_ID) return;

        if (empty($credentials[self::SENDING_METHOD_ID.'_apikey'])) {
            acym_sendAjaxResponse(acym_translation('ACYM_MISSING_API_KEY'), [], false);
        }

        $response = $this->callApiSendingMethod(
            'get_credits',
            [],
            [
                'API-KEY' => $credentials[self::SENDING_METHOD_ID.'_apikey'],
            ]
        );

        if (empty($response)) {
            $errorMsg = acym_translation('ACYM_NO_ANSWER');
            acym_sendAjaxResponse(acym_translationSprintf('ACYM_ERROR_OCCURRED_WHILE_CALLING_API', $errorMsg), [], false);
        } elseif (!empty($response['error_curl'])) {
            acym_sendAjaxResponse(acym_translationSprintf('ACYM_ERROR_OCCURRED_WHILE_CALLING_API', $response['error_curl']), [], false);
        } elseif (!empty($response['message']) && $response['message'] == 'Invalid private key') {
            acym_sendAjaxResponse(acym_translation('ACYM_AUTHENTICATION_FAILS_WITH_API_KEY'), [], false);
        } elseif (!empty($response['message']) && !$this->checkSuccessCode($this->responseCode)) {
            if ($this->responseCode === 401) {
                acym_sendAjaxResponse(
                    acym_translationSprintf(
                        'ACYM_SENDING_METHOD_ERROR_WHILE_ACTION',
                        self::SENDING_METHOD_NAME,
                        acym_translation('ACYM_GETTING_REMAINING_CREDITS'),
                        $response['message']
                    ),
                    [],
                    false
                );
            }
            acym_sendAjaxResponse($response['message'], [], false);
        } else {
            acym_sendAjaxResponse(acym_translation('ACYM_API_KEY_CORRECT'));
        }
    }

    private function updateDomainRates($domainsRates)
    {
        $domains = $this->config->get(self::SENDING_METHOD_ID.'_domains', []);

        if (empty($domains)) {
            return;
        }

        $domains = @json_decode($domains, true);

        foreach ($domainsRates as $oneDomain => $oneRate) {
            if (empty($domains[$oneDomain])) continue;
            foreach ($oneRate as $rateKey => $rateValue) {
                $domains[$oneDomain][$rateKey] = $rateValue;
            }
        }

        $this->config->save([self::SENDING_METHOD_ID.'_domains' => json_encode($domains)]);
    }

    public function onAcymProcessQueueExternalSendingCampaign(&$externalSending, $transactional = false)
    {
        if ($this->config->get('mailer_method') == self::SENDING_METHOD_ID) $externalSending = false;
    }

    public function onAcymSendEmail(&$response, $mailerHelper, $to, $from, $replyTo, $bcc = [], $attachments = [])
    {
        if ($mailerHelper->externalMailer != self::SENDING_METHOD_ID) {
            return;
        }

        $apikey = $this->config->get(self::SENDING_METHOD_ID.'_apikey');
        if (empty($apikey)) {
            $response['error'] = true;
            $response['message'] = acym_translation('ACYM_MISSING_API_KEY');

            return;
        }

        if ($this->isUnsubscribeLinkMissing($mailerHelper)) {
            $response['error'] = true;
            $response['message'] = acym_translation('ACYM_MISSING_UNSUBSCRIBE_LINK');

            return;
        }

        $domainsUsed = [
            acym_getDomain($from['email']),
            acym_getDomain($replyTo['email']),
        ];
        $bounceAddress = $this->config->get('bounce_email');
        if (!empty($bounceAddress)) {
            $domainsUsed[] = acym_getDomain($bounceAddress);
        }

        $domainsUsed = array_unique($domainsUsed);

        $verifiedDomains = $this->getVerifiedDomains();

        $unverifiedDomains = array_diff($domainsUsed, $verifiedDomains);
        if (!empty($unverifiedDomains)) {
            $response['error'] = true;
            $response['message'] = acym_translationSprintf('ACYM_UNVERIFIED_DOMAINS_PREVENTING_EMAILS_FROM_BEING_SENT_X', implode(', ', $unverifiedDomains));

            return;
        }

        $responseMailer = $this->callApiSendingMethod(
            'send',
            [
                'email' => $mailerHelper->getSentMIMEMessage(),
                'domainsUsed' => $domainsUsed,
            ],
            [],
            'POST'
        );
        if (!empty($result['logs'])) {
            $js = 'jQuery(function() { console.log("'.acym_escape($result['logs']).'") });';
            acym_addScript(true, $js);
        }

        if (!empty($responseMailer['error_curl'])) {
            $response['error'] = true;
            $response['message'] = acym_translationSprintf('ACYM_ERROR_OCCURRED_WHILE_CALLING_API', $responseMailer['error_curl']);
        } elseif (!$this->checkSuccessCode($this->responseCode)) {
            $response['error'] = true;
            $response['message'] = $this->translate($responseMailer);

            if ($response['message'] === acym_translation('ACYM_NO_CREDITS_LEFT')) {
                $response['message'] = acym_translation('ACYM_NOT_ENOUGH_CREDITS').' : ';
                $response['message'] .= '<a target="_blank" class="acym__color__blue" href="'.ACYM_ACYMAILING_WEBSITE.'">';
                $response['message'] .= acym_translation('ACYM_GET_MORE_CREDITS');
                $response['message'] .= '</a>';
            }
        } else {
            $response['error'] = false;
        }
    }

    public function replaceUserInformation(&$email, &$user, $send = true)
    {
        if (empty($email->externalMailer) || $email->externalMailer !== self::SENDING_METHOD_ID) return;

        $bounceAddress = $this->config->get('bounce_email');
        if (!empty($bounceAddress) && method_exists($email, 'addCustomHeader')) {
            $email->addCustomHeader('Return-Path', $bounceAddress);
        }
    }

    public function ajaxCheckDomain()
    {
        $sendingMethod = acym_getVar('string', 'sendingMethod', '');
        if ($sendingMethod != self::SENDING_METHOD_ID) {
            acym_sendAjaxResponse();
        }

        $domains = json_decode($this->config->get(self::SENDING_METHOD_ID.'_domains', '[]'), true);
        if (empty($domains)) {
            acym_sendAjaxResponse();
        }

        $remainingDomains = $this->checkDomainsDNS($domains);

        if (empty($remainingDomains)) {
            acym_sendAjaxResponse('', ['domains' => $domains]);
        }

        $prepareDomains = array_map(
            function ($domain) {
                return $domain['domain'];
            },
            $remainingDomains
        );

        $responseApi = $this->callApiSendingMethod(
            'getDomainStatus',
            ['domains' => $prepareDomains],
            [],
            'POST'
        );

        if (empty($responseApi)) {
            acym_sendAjaxResponse('', ['domains' => $domains]);
        }

        if (!$this->checkSuccessCode($this->responseCode)) {
            acym_sendAjaxResponse(acym_translation('ACYM_ERROR_ON_CALL_ACYBA_WEBSITE').': '.$responseApi['message'], ['domains' => $domains], false);
        }

        $errorDomains = [];
        foreach ($domains as $key => $oneDomain) {
            $domainName = $oneDomain['domain'];
            if (!array_key_exists($domainName, $responseApi['data']) || !is_array($responseApi['data'][$domainName])) {
                $errorDomains[] = acym_translation('ACYM_ERROR_ON_CALL_ACYBA_WEBSITE').': '.$responseApi['data'][$domainName].': '.$domainName;
                continue;
            }

            if (empty($responseApi['data'][$domainName]['code'])) {
                $domains[$key] = array_merge($domains[$key], $responseApi['data'][$domainName]);
                continue;
            }

            if (!empty(self::TRANSLATIONS[$responseApi['data'][$domainName]['code']])) {
                $message = self::TRANSLATIONS[$responseApi['data'][$domainName]['code']];
            } else {
                if (empty($responseApi['data'][$domainName]['message'])) {
                    $message = 'ACYM_DOMAIN_DEFAULT_ERROR';
                } else {
                    $message = $this->displayMessage($responseApi['data'][$domainName]['message']);
                }
            }
            acym_enqueueMessage(
                acym_translation('ACYM_ERROR_ON_CALL_ACYBA_WEBSITE').': '.acym_translationSprintf(
                    $message,
                    $domainName
                ),
                'error'
            );

            if (!empty($responseApi['data'][$domainName]['logs'])) {
                $js = 'jQuery(function() { console.log("'.acym_escape($responseApi['data'][$domainName]['logs']).'") });';
                acym_addScript(true, $js);
            }
        }

        $this->config->save([self::SENDING_METHOD_ID.'_domains' => json_encode($domains)]);

        if (!empty($errorDomains)) {
            acym_sendAjaxResponse(implode(', ', $errorDomains), [], false);
        }

        acym_sendAjaxResponse('', ['domains' => $domains]);
    }

    public function checkDomainsDNS($domains): array
    {
        $remainingDomains = [];
        foreach ($domains as $oneDomain) {
            if (empty($oneDomain['CnameRecords'])) {
                $remainingDomains[] = $oneDomain;
                continue;
            }

            if ($oneDomain['status'] == 'SUCCESS') {
                continue;
            }

            $shouldCheckDomain = true;
            foreach ($oneDomain['CnameRecords'] as $oneRecord) {
                $dnsEntries = @dns_get_record($oneRecord['name'], DNS_CNAME);
                if (empty($dnsEntries)) {
                    $shouldCheckDomain = false;

                    $domainPosition = strpos($oneRecord['name'], '_domainkey') + 10;
                    $domainName = substr($oneRecord['name'], $domainPosition);
                    $entryName = substr($oneRecord['name'], 0, $domainPosition);
                    $doubleDomainEntry = $oneRecord['name'].$domainName;

                    $dnsEntries = @dns_get_record($doubleDomainEntry, DNS_CNAME);
                    if (!empty($dnsEntries)) {
                        $errors = [];
                        $errors[] = acym_translationSprintf('ACYM_ACYMAILER_MALFORMED_CNAME', $doubleDomainEntry);
                        $errors[] = acym_translationSprintf('ACYM_ACYMAILER_CNAME_TRY_WITHOUT_DOMAIN', $entryName, $oneRecord['value']);
                        acym_sendAjaxResponse(implode('<br/> ', $errors), ['domain' => substr($domainName, 1)], false);
                    }

                    break;
                }

                $entryFound = false;
                if (!is_array($dnsEntries)) {
                    $dnsEntries = [$dnsEntries];
                }
                foreach ($dnsEntries as $oneEntry) {
                    if ($oneEntry['type'] !== 'CNAME' || $oneEntry['host'] !== $oneRecord['name']) {
                        continue;
                    }
                    if ($oneEntry['target'] === $oneRecord['value']) {
                        $entryFound = true;
                        break;
                    } else {
                        acym_enqueueMessage(acym_translationSprintf('ACYM_DOMAIN_CHECK_BAD_SYNTAX', $oneRecord['name'], $oneDomain['domain']), 'error');
                    }
                }
                if (!$entryFound) {
                    $shouldCheckDomain = false;
                }
            }
            if ($shouldCheckDomain) {
                $remainingDomains[] = $oneDomain;
            }
        }

        return $remainingDomains;
    }

    public function onAcymDeleteDomain()
    {
        $oneDomain = acym_getVar('string', 'oneDomain', '');

        if (empty($oneDomain)) {
            return;
        }

        $apikey = $this->config->get(self::SENDING_METHOD_ID.'_apikey');
        if (empty($apikey)) {
            acym_sendAjaxResponse(acym_translation('ACYM_MISSING_API_KEY'), [], false);
        }

        $responseApi = $this->callApiSendingMethod(
            'deleteDomainIdentity',
            ['domain' => $oneDomain, 'siteUrl' => acym_baseURI()],
            [],
            'POST'
        );

        if (!empty($responseApi['error_curl'])) {
            $message = acym_translationSprintf('ACYM_ERROR_OCCURRED_WHILE_CALLING_API', $responseApi['error_curl']);
            acym_sendAjaxResponse($message, [], false);
        } elseif (!empty($responseApi['message']) && $responseApi['message'] === 'Invalid private key') {
            acym_sendAjaxResponse(acym_translation('ACYM_AUTHENTICATION_FAILS_WITH_API_KEY'), [], false);
        } elseif (!$this->checkSuccessCode($this->responseCode) && (empty($responseApi['code']) || !in_array($responseApi['code'], [5, 4, 3]))) {

            $message = acym_translation('ACYM_ERROR_ON_CALL_ACYBA_WEBSITE').': '.$this->translate($responseApi);
            $logs = !empty($responseApi['logs']) ? $responseApi['logs'] : '';
            acym_sendAjaxResponse($message, ['logs' => $logs], false);
        } else {
            $field = self::SENDING_METHOD_ID.'_domains';
            $domains = json_decode($this->config->get($field, '[]'), true);
            $domains = array_filter($domains, function ($domain) use ($oneDomain) {
                return $domain['domain'] != $oneDomain;
            });

            $newConfig = new \stdClass();
            $newConfig->$field = json_encode($domains);
            $this->config->save($newConfig);

            acym_sendAjaxResponse();
        }
    }

    public function ajaxAddDomain()
    {
        $oneDomain = acym_getVar('string', 'oneDomain', '');

        $this->config->save(['mailer_method' => self::SENDING_METHOD_ID]);

        if (empty($oneDomain)) {
            acym_sendAjaxResponse(acym_translation('ACYM_MISSING_DOMAIN'), [], false);
        }

        $apikey = $this->config->get(self::SENDING_METHOD_ID.'_apikey');
        if (empty($apikey)) {
            acym_sendAjaxResponse(acym_translation('ACYM_MISSING_API_KEY'), [], false);
        }

        $domainValidation = preg_match('/^(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z0-9][a-z0-9-]{0,61}[a-z0-9]$/u', $oneDomain);
        if (!$domainValidation) {
            $message = acym_translationSprintf('ACYM_THE_DOMAIN_X_IS_NOT_VALID', $oneDomain);
            acym_sendAjaxResponse($message, ['type' => 'DOMAIN_VALIDATION'], false);
        }

        $field = self::SENDING_METHOD_ID.'_domains';
        $domains = $this->config->get($field, []);

        if (!empty($domains)) {
            $domains = @json_decode($domains, true);
        }

        if (!empty($domains[$oneDomain])) {
            acym_sendAjaxResponse(acym_translation('ACYM_DOMAIN_ALREADY_SENT'), [], false);
        }

        $responseApi = $this->callApiSendingMethod(
            'createDomainIdentity',
            ['domain' => $oneDomain, 'siteUrl' => acym_baseURI()],
            [],
            'POST'
        );

        if (!empty($responseApi['error_curl'])) {
            $message = acym_translationSprintf('ACYM_ERROR_OCCURRED_WHILE_CALLING_API', $responseApi['error_curl']);
            acym_sendAjaxResponse($message, [], false);
        } elseif (!empty($responseApi['message']) && $responseApi['message'] === 'Invalid API-KEY') {
            acym_sendAjaxResponse(acym_translation('ACYM_AUTHENTICATION_FAILS_WITH_API_KEY'), [], false);
        } elseif (!$this->checkSuccessCode($this->responseCode)) {
            if (empty($responseApi['code']) || empty(self::TRANSLATIONS[$responseApi['code']])) {
                $errorCodeTranslationKey = empty($responseApi['message']) ? 'ACYM_DOMAIN_DEFAULT_ERROR' : $this->displayMessage($responseApi['message']);
            } else {
                $errorCodeTranslationKey = self::TRANSLATIONS[$responseApi['code']];
            }
            $message = acym_translationSprintf($errorCodeTranslationKey, $oneDomain);
            acym_sendAjaxResponse($message, [], false);
        } elseif ($this->responseCode === 201) {
            $domains[$oneDomain] = [
                'domain' => $oneDomain,
                'CnameRecords' => $responseApi['cnameRecords'],
                'status' => 'PENDING',
            ];
            $this->config->save([$field => json_encode($domains)]);
            acym_sendAjaxResponse($this->translate($responseApi), ['cnameRecords' => $responseApi['cnameRecords']]);
        } else {
            $cnameRecords = $responseApi['data'][$oneDomain]['cnameRecords'];
            $status = $responseApi['data'][$oneDomain]['status'];
            $domains[$oneDomain] = [
                'domain' => $oneDomain,
                'CnameRecords' => $cnameRecords,
                'status' => $status,
            ];
            $this->config->save([$field => json_encode($domains)]);

            acym_sendAjaxResponse(
                $this->translate($responseApi),
                ['cnameRecords' => $cnameRecords, 'alreadyVerified' => $status === 'SUCCESS']
            );
        }
    }

    public function getDomainsUnVerified(): array
    {
        if ($this->config->get('mailer_method') !== self::SENDING_METHOD_ID) {
            return [];
        }

        $allDomains = [];

        $allDomains[] = acym_getDomain($this->config->get('from_email'));
        if ($this->config->get('from_as_replyto') === '0') {
            $allDomains[] = acym_getDomain($this->config->get('replyto_email'));
        }

        if ($this->config->get('multilingual') === '1') {
            $senderInfoTranslation = $this->config->get('sender_info_translation');
            if (!empty($senderInfoTranslation)) {
                $senderInfoTranslation = json_decode($senderInfoTranslation, true);
                foreach ($senderInfoTranslation as $oneSenderInfo) {
                    $allDomains[] = acym_getDomain($oneSenderInfo['from_email']);
                    $allDomains[] = acym_getDomain($oneSenderInfo['replyto_email']);
                }
            }
        }

        $allDomains[] = acym_getDomain($this->config->get('bounce_email'));
        $allDomains = array_filter(array_unique($allDomains));

        $verifiedDomains = $this->getVerifiedDomains();

        return array_diff($allDomains, $verifiedDomains);
    }

    private function getVerifiedDomains(): array
    {
        $verifiedDomains = [];
        $domainStatuses = json_decode($this->config->get(self::SENDING_METHOD_ID.'_domains', '[]'), true);
        if (!empty($domainStatuses)) {
            foreach ($domainStatuses as $domain => $info) {
                if ($info['status'] === 'SUCCESS') {
                    $verifiedDomains[] = $domain;
                }
            }
        }

        return $verifiedDomains;
    }

    public function onAcymDisplayPage()
    {
        if (intval($this->config->get('walk_through')) === 1) {
            return;
        }

        $unverifiedDomains = $this->getDomainsUnVerified();

        if (!empty($unverifiedDomains)) {
            $message = acym_translationSprintf('ACYM_UNVERIFIED_DOMAINS_PREVENTING_EMAILS_FROM_BEING_SENT_X', implode(', ', $unverifiedDomains));
            acym_enqueueMessage($message, 'warning');
        }
    }

    private function displayMessage($message): string
    {
        $correspondances = [
            'AlreadyExistsException' => 'ACYM_DOMAIN_X_ALREADY_EXIST',
            'TooManyRequestsException' => 'ACYM_DOMAIN_TO_MANY_REQUEST',
            'NotFoundException' => 'ACYM_DOMAIN_NOT_FOUND',
            'NotExistException' => 'ACYM_CANT_DELETE_DOMAIN',
        ];

        if (empty($message) || empty($correspondances[$message])) {
            return 'ACYM_DOMAIN_DEFAULT_ERROR';
        }

        return $correspondances[$message];
    }

    public function onAcymGetCreditRemainingSendingMethod(&$html, $reloading = false)
    {
        $sendingMethod = $this->config->get('mailer_method', '');
        if (empty($sendingMethod) || $sendingMethod != self::SENDING_METHOD_ID) return;

        $apiKey = $this->config->get(self::SENDING_METHOD_ID.'_apikey', '');
        if (empty($apiKey)) return;

        $response = $this->getRemainingCredits($reloading);

        if (!isset($response['remaining_credits'])) {
            if (empty($this->translate($response))) {
                $html = acym_translation('ACYM_ERROR_CONTACT_ACYMAILING');
            } else {
                $html = acym_translationSprintf(
                    'ACYM_SENDING_METHOD_ERROR_WHILE_ACTION',
                    self::SENDING_METHOD_NAME,
                    acym_translation('ACYM_GETTING_REMAINING_CREDITS'),
                    $this->translate($response)
                );
            }

            return;
        }

        $html = acym_translationSprintf(
            'ACYM_SENDING_METHOD_X_UNITY',
            self::SENDING_METHOD_NAME,
            acym_tooltip(
                $response['remaining_credits'],
                acym_translation('ACYM_GET_MORE_CREDITS'),
                'credits_remaining',
                '',
                ACYM_ACYMAILING_WEBSITE
            ),
            '<span class="acym_not_bold">'.acym_translation('ACYM_CREDITS_REMAINING').'</span>'
        );
    }

    private function getRemainingCredits($forceReload = false)
    {
        $time = time();
        $lastCheck = $this->config->get(self::SENDING_METHOD_ID.'_last_credits_check', 0);
        $lastDetails = $this->config->get(self::SENDING_METHOD_ID.'_credits_details', []);

        if (!$forceReload && !empty($lastDetails) && $lastCheck > $time - self::CREDITS_RELOAD_DELAY) {
            return json_decode($lastDetails, true);
        }

        $response = $this->callApiSendingMethod('get_credits');

        $this->config->save([
            self::SENDING_METHOD_ID.'_credits_details' => json_encode($response),
            self::SENDING_METHOD_ID.'_last_credits_check' => $time,
        ]);

        if (!empty($response['domains'])) {
            $this->updateDomainRates($response['domains']);
        }

        return $response;
    }

    public function onAcymCreditsLeft(&$creditsLeft)
    {
        $sendingMethod = $this->config->get('mailer_method', '');
        if (empty($sendingMethod) || $sendingMethod != self::SENDING_METHOD_ID) return;

        $apiKey = $this->config->get(self::SENDING_METHOD_ID.'_apikey', '');
        if (empty($apiKey)) {
            $creditsLeft = 0;

            return;
        }

        $credits = $this->getRemainingCredits(true);
        if (empty($credits['remaining_credits'])) {
            $creditsLeft = 0;
        } else {
            $creditsLeft = $credits['remaining_credits'];
        }
    }

    private function checkSuccessCode($responseCode): bool
    {
        $successCodes = [200, 201, 204];

        return in_array($responseCode, $successCodes);
    }

    private function translate($response)
    {
        if (!isset($response['code']) && empty($response['message'])) return '';

        $correspondence = '';
        $messageData = '';


        if (isset($response['code']) && !empty(self::TRANSLATIONS[$response['code']])) {
            $correspondence = self::TRANSLATIONS[$response['code']];
        }

        if (!empty($response['messageData'])) {
            $messageData = $response['messageData'];
        }

        if (empty($correspondence)) {
            if (empty($messageData)) {
                return $response['message'];
            }

            return acym_translationVsprintf($response['message'], $messageData, false);
        }

        if (empty($messageData)) {
            $finalMessage = acym_translation($correspondence);
        } else {
            $finalMessage = acym_translationVsprintf($correspondence, $messageData);
        }

        if (in_array($response['code'], $this->errorCodes)) {
            $this->errors[] = $finalMessage;
            $this->errorCallback();
        }

        return $finalMessage;
    }

    public function onAcymAttachLicense($licenseKey)
    {
        if (empty($licenseKey)) {
            return;
        }

        $response = $this->callApiSendingMethod(self::SENDING_METHOD_API_URL.'public/licenses/'.$licenseKey);

        if (!empty($response['id'])) {
            $this->config->save([self::SENDING_METHOD_ID.'_apikey' => $response['id']]);
        }
    }

    public function onAcymDetachLicense()
    {
        $this->config->save([self::SENDING_METHOD_ID.'_apikey' => '']);
    }

    private function isUnsubscribeLinkMissing($mailerHelper): bool
    {
        if ($mailerHelper->isTest || $mailerHelper->isForward || $mailerHelper->isSpamTest) {
            return false;
        }

        if (empty($mailerHelper->mail)) {
            return false;
        }

        static $mailClass = null;
        if ($mailClass === null) {
            $mailClass = new MailClass();
        }

        if (in_array($mailerHelper->mail->type, [$mailClass::TYPE_NOTIFICATION, $mailClass::TYPE_OVERRIDE, $mailClass::TYPE_UNSUBSCRIBE, $mailClass::TYPE_TEMPLATE])) {
            return false;
        }

        if (strpos($mailerHelper->mail->body, '{unsubscribe}') !== false || strpos($mailerHelper->mail->body, 'task=unsubscribe') !== false) {
            return false;
        }

        return true;
    }
}
