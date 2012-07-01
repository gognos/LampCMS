<?php
/**
 *
 * License, TERMS and CONDITIONS
 *
 * This software is licensed under the GNU LESSER GENERAL PUBLIC LICENSE (LGPL) version 3
 * Please read the license here : http://www.gnu.org/licenses/lgpl-3.0.txt
 *
 *  Redistribution and use in source and binary forms, with or without
 *  modification, are permitted provided that the following conditions are met:
 * 1. Redistributions of source code must retain the above copyright
 *    notice, this list of conditions and the following disclaimer.
 * 2. Redistributions in binary form must reproduce the above copyright
 *    notice, this list of conditions and the following disclaimer in the
 *    documentation and/or other materials provided with the distribution.
 * 3. The name of the author may not be used to endorse or promote products
 *    derived from this software without specific prior written permission.
 *
 * ATTRIBUTION REQUIRED
 * 4. All web pages generated by the use of this software, or at least
 *       the page that lists the recent questions (usually home page) must include
 *    a link to the http://www.lampcms.com and text of the link must indicate that
 *    the website\'s Questions/Answers functionality is powered by lampcms.com
 *    An example of acceptable link would be "Powered by <a href="http://www.lampcms.com">LampCMS</a>"
 *    The location of the link is not important, it can be in the footer of the page
 *    but it must not be hidden by style attributes
 *
 * THIS SOFTWARE IS PROVIDED BY THE AUTHOR "AS IS" AND ANY EXPRESS OR IMPLIED
 * WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF
 * MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED.
 * IN NO EVENT SHALL THE FREEBSD PROJECT OR CONTRIBUTORS BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF
 * THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This product includes GeoLite data created by MaxMind,
 *  available from http://www.maxmind.com/
 *
 *
 * @author     Dmitri Snytkine <cms@lampcms.com>
 * @copyright  2005-2012 (or current year) Dmitri Snytkine
 * @license    http://www.gnu.org/licenses/lgpl-3.0.txt GNU LESSER GENERAL PUBLIC LICENSE (LGPL) version 3
 * @link       http://www.lampcms.com   Lampcms.com project
 * @version    Release: @package_version@
 *
 *
 */

namespace Lampcms\Modules\Observers;

use \Lampcms\Utf8String;
use \Lampcms\Twitter;
use \Lampcms\Tweet;
use \Lampcms\Bitly;


/**
 * This observer will post a tweet to Twitter API
 * In case of Question it will prefix some prefix like
 * #question + title + link + via @ourtwitter
 * It will test the length of title and will not add
 * prefix and not add @ourtwitter if that would cause
 * tweet to go over 140
 * It will truncate title if title is
 * already over 140
 *
 * In case of answer it will prefix I answered: $title + link
 *
 * It will try to use bit.ly shortener is it's setup
 * in !config.ini or will use our own domain/q/$id/#ans$aid
 *  It's always best to setup bit.ly api to get
 *  shorter url
 *
 * @author Dmitri Snytkine
 *
 */
class PostTweet extends \Lampcms\Event\Observer
{

    protected $text;

    protected $link;

    public function main()
    {
        d('get event: ' . $this->eventName);
        $a = $this->Registry->Request->getArray();
        if (empty($a['tweet'])) {
            d('tweet checkbox not checked');
            /**
             * Set the preference in Viewer object
             * for that "Post to Twitter" checkbox to be not checked
             * This is just in case it was checked before
             */
            $this->Registry->Viewer['b_tw'] = false;
            return;
        }

        /**
         * First if this site does not have support for Twitter API
         * OR if User does not have Twitter credentials then
         * there is nothing to do here
         * This is unlikely because user without Twitter credentials
         * will not get to see the checkbox to post to Twitter
         * but still it's better to check just to be sure
         */
        if (!extension_loaded('oauth')) {
            d('oauth extension not present');
            return;
        }

        try {
            $aTW = $this->Registry->Ini->getSection('TWITTER');
            if (empty($aTW) || empty($aTW['TWITTER_OAUTH_KEY']) || empty($aTW['TWITTER_OAUTH_SECRET'])) {
                d('Twitter API not enabled on this site');

                return;
            }
        } catch (\Lampcms\IniException $e) {
            d($e->getMessage());
            return;
        }

        if ('' === (string)$this->Registry->Viewer->getTwitterSecret()) {
            d('User does not have Twitter token');
            return;
        }

        /**
         * Now we know that user checked that checkbox
         * to Tweet content
         * and we now going to save this preference
         * in User object
         *
         */
        $this->Registry->Viewer['b_tw'] = true;

        switch ($this->eventName) {
            case 'onNewQuestion':
            case 'onNewAnswer':
                $this->tweet();
                break;
        }
    }


    /**
     * Post a Tweet with link to this
     * question
     */
    protected function tweet()
    {

        try {
            $reward = $this->Registry->Ini->POINTS->SHARED_CONTENT;
            $User = $this->Registry->Viewer;
            $oTweet = new Tweet();
            $oBitly = new Bitly($this->Registry->Ini->getSection('BITLY'));
            $oTwitter = new Twitter($this->Registry);
            $Resource = $this->obj;
            $Mongo = $this->Registry->Mongo;
            d('cp');
        } catch (\Exception $e) {
            d('Unable to post tweet because of this exception: ' . $e->getMessage() . ' in file: ' . $e->getFile() . ' on line: ' . $e->getLine());
            return;
        }

        $func = function() use ($oTweet, $oBitly, $oTwitter, $Resource, $User, $reward, $Mongo)
        {
            $result = $oTweet->post($oTwitter, $oBitly, $Resource);
            if (!empty($result) && is_array($result)) {

                /**
                 * If status is OK (Tweet was posted)
                 * then reward the user with points!
                 */
                if (!empty($result['id_str']) && ('200' == $result['http_code'])) {
                    $User->setReputation($reward);

                    /**
                     * Now need to also record Tweet Status data
                     * to TWEETS collection
                     */

                    try {
                        $coll = $Mongo->TWEETS;
                        $coll->ensureIndex(array('i_uid' => 1));

                        $tw_uid = (!empty($result['user']) && !empty($result['user']['id_str'])) ? $result['user']['id_str'] : null;
                        $tw_username = (!empty($result['user']) && !empty($result['user']['screen_name'])) ? $result['user']['screen_name'] : null;
                        /**
                         * Record Tweet status to TWEETS collection.
                         * Later can query Twitter to find
                         * replies to these Tweets and add them
                         * as "comments" to this Question or Answer
                         *
                         * HINT: if i_rid !== i_qid then it's an ANSWER
                         * if these are the same then it's a Question
                         * @var array
                         */
                        $aData = array(
                            '_id' => $result['id_str'],
                            'i_uid' => $User->getUid(),
                            'i_rid' => $Resource->getResourceId(),
                            'i_qid' => $Resource->getQuestionId(),
                            'tw_user_id_str' => $tw_uid,
                            'tw_username' => $tw_username,
                            'i_ts' => time(),
                            'h_ts' => date('r')
                        );

                        $coll->save($aData);

                    } catch (\Exception $e) {
                        if (function_exists('e')) {
                            e('Unable to save data to TWEETS collection because of ' . $e->getMessage() . ' in file: ' . $e->getFile() . ' on line: ' . $e->getLine());
                        }
                    }
                }
            }
        };
        d('cp');

        \Lampcms\runLater($func);
    }

}
