<?php
/*
 * Copyright 2008 Google Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

/**
 * Pre-defined provider class for Netlog (http://www.netlog.com)
 * @author Chris Chabot
 */
class osapiNetlogProvider extends osapiProvider {
  public function __construct(osapiHttpProvider $httpProvider = null, $apiDomain = 'http://en.api.netlog.com') {
  	//http://en.api.netlog.com
  	//$apiDomain = "http://beta.nl.api.netlog.com"; //"http://nl.api.netlog.com" when we go live!;
    parent::__construct($apiDomain ."/oauth/request_token", $apiDomain . "/oauth/authorize", $apiDomain . "/oauth/access_token",  $apiDomain ."/opensocial/social/rest", $apiDomain . "/opensocial/social/rpc", "Netlog", true, $httpProvider);
  }

  public function preRequestProcess(&$request, &$method, &$url, &$headers, osapiAuth &$signer) {
    if (method_exists($signer, 'setUseBodyHash')) {
      $signer->setUseBodyHash(true);
    }
  }
}