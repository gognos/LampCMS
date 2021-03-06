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
 *    the website's Questions/Answers functionality is powered by lampcms.com
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


namespace Lampcms\Controllers;

use Lampcms\TagsTokenizer;

use Lampcms\WebPage;
use Lampcms\Paginator;
use Lampcms\Template\Urhere;
use Lampcms\Utf8String;
use Lampcms\TagsTagsTokenizer;

/**
 * Controller to view unanswered questions
 * pages. Unanswered pages could show:
 * no accepted answer, no answer at all,
 * tagged unanswered and 'my unanswered'
 *
 * In any case the 'tags' block will always be showing
 * "unanswered" tags
 *
 * @author Dmitri Snytkine
 *
 */
class Unanswered extends Viewquestions
{

    /**
     * Indicates the current tab
     *
     * @var string
     */
    protected $qtab = 'unanswered';

    protected $pagerPath = '{_unanswered_}';


    protected $aTags;

    /**
     * Tags string from url
     * it is urldecoded
     *
     * @var string
     */
    protected $tags = '';

    /**
     * Tags
     * rawTags is the string value of tags
     * passed in url without applying urlencode
     * or any of the escaping of the html tags.
     * This is a very dangerous string
     * It is used only as a value of
     * pager path because pager class
     * applies urlencode to the string
     * before generating urls of pagination
     *
     *
     * @var string
     */
    protected $rawTags = '';

    /**
     * Overrides the use of $this->Router->getRealPageID
     * because we don't want a possibility of
     * extracting pageID from a tag name when a tag contains number
     *
     * @return object $this
     */
    public function setPageID()
    {
        $this->pageID = (int)$this->Router->getPageID();

        return $this;
    }

    /**
     * Select items according to conditions passed in GET
     * Conditions can be == 'unanswered', 'hot', 'recent' (default)
     *
     * @return \Lampcms\Controllers\Unanswered
     */
    protected function getCursor()
    {

        $urlParts = $this->Registry->Ini->getSection('URI_PARTS');

        $cond = $this->Router->getSegment(1, 's', $urlParts['SORT_RECENT']);
        d('cond: ' . $cond);

        /**
         * Default sort is by timestamp Descending
         * meaning most recent should be on top
         *
         */
        $sort = array('i_ts' => -1);

        $this->title = '@@Questions with no accepted answer@@';

        switch ( $cond ) {


            /**
             * Most answers/comments/views
             * There is an activity counter
             * 1 point per view, 10 point per comment,
             * 50 points per answer
             * but still limit to 30 days
             * Cache Tags for 1 day only
             * uncache onQuestionVote, onQuestionComment
             */
            case $urlParts['COND_NOANSWERS']:
                $this->title = '@@Questions with no answers@@';
                $this->pagerPath .= '/{_COND_NOANSWERS_}';
                $where         = array('i_ans' => 0);
                $this->typeDiv = Urhere::factory($this->Registry)->get('tplQuntypes', 'noanswer');
                break;

            case $urlParts['COND_TAGGED']:
                $where = array('i_sel_ans' => null,
                               'a_tags'    => array('$all' => $this->getTags()));
                $this->pagerPath .= '/{_COND_TAGGED_}' . $this->rawTags;
                $this->typeDiv = Urhere::factory($this->Registry)->get('tplQuntypes', 'newest');
                $this->makeFollowTagButton();

                $replaced = array(
                    'tags' => \str_replace(' ', ' + ', $this->tags),
                    'text' => '@@Tagged@@'
                );

                $this->counterTaggedText = \tplCounterblocksub::parse($replaced, false);
                break;

            /**
             * Default is all questions
             * with no selected answer!
             */
            default:
                $this->title   = '@@Questions with no accepted answer@@';
                $where         = array('i_sel_ans' => null);
                $this->typeDiv = Urhere::factory($this->Registry)->get('tplQuntypes', 'newest');
        }

        /**
         * Exclude deleted items
         */
        $where['i_del_ts'] = null;

        $this->Cursor = $this->Registry->Mongo->QUESTIONS->find($where, $this->aFields);
        $this->count  = $this->Cursor->count(true);
        d('$this->Cursor: ' . gettype($this->Cursor) . ' $this->count: ' . $this->count);
        $this->Cursor->sort($sort);

        return $this;
    }


    protected function addTagFollowers()
    {
        $s = \Lampcms\ShowFollowers::factory($this->Registry)->getTagFollowers($this->aTags[0]);
        d('tag followers: ' . $s);
        $this->aPageVars['side'] .= '<div class="fr cb w90 lg rounded3 pl10 mb10">' . $s . '</div>';

        return $this;
    }


    /**
     * Extract value of tags from
     * query string and turn into array
     * runs value of Request through urldecode because
     * unicode tags would be percent-encoded in the url
     *
     * @return array of tags passed in query string
     */
    protected function getTags()
    {

        if (empty($this->aTags)) {

            /**
             * And now a workaround
             * for the genocidal RewriteRule bug
             * that obliterates the urlencoded chars
             * during the rewrite
             * so instead we must work directly
             * with $_SERVER['REQUEST_URI']
             * $_SERVER['REQUEST_URI'] is consistently
             * the same on Apache and on Lighttpd when
             * php is run as fastcgi
             * The rewrite on Lighttpd does not have
             * this genocidal bug, but for consistency
             * we still working with $_SERVER['REQUEST_URI']
             * regardless of the server
             */
            if (!empty($_SERVER) && !empty($_SERVER['REQUEST_URI'])) {
                /**
                 * Must use regex because REQUEST_URI
                 * may contain also a pageID after the last /
                 * so must extract part from
                 * between /tagged/ and next /
                 *
                 * $r is something like this: /tagged/tag%2B%2B/
                 */
                $r = $_SERVER['REQUEST_URI'];
                $m = \preg_match('/\/tagged\/([^\/]+)([\/]{0,1})/i', $r, $matches);
                d('matches: ' . print_r($matches, 1));
                if ($matches && !empty($matches[1])) {
                    $tags = $matches[1];

                    d('tags: ' . $tags);
                    $this->tags = \urldecode($tags);

                }

            } else {
                /**
                 * That's hopefully is OK
                 * because Apache always has REQUEST_URI
                 * and if it's not available here
                 * then hopefully this is not an Apache server
                 * and it's possible the rewrite worked without this bug
                 */
                d('no REQUEST_URI available');
                $tags       = $this->Request['tags'];
                $this->tags = \urldecode($tags);
            }

            $this->rawTags = $this->tags;
            /**
             * Important step to prevent
             * script or html injection in url GET string
             * Cannot use htmlspecialchars because we don't want to
             * also encode the &
             *
             */
            $this->tags = \str_replace(array('<', '>'), array('&lt;', '&gt;'), $this->tags);

           // $this->rawTags = $tags; //TagsTokenizer::factory($Utf8Tags)->getArrayCopy();
            $this->title   = $this->tags;

            if (empty($this->tags)) {
                return array();
            }

            /**
             * $this->tags are now urldecoded
             * If this does not work well them try
             * to use $tags instead
             */
            $Utf8Tags = Utf8String::stringFactory($this->tags);

            /**
             * @todo the this->tags now have htmlspecialchars
             *       which may not be what we want in this->aTags
             *       We probably want this->aTags to be raw tags just like
             *       they were submitted in request.
             *
             */
            $this->aTags = TagsTokenizer::factory($Utf8Tags)->getArrayCopy();

            d('aTags: ' . \print_r($this->aTags, 1));
        }

        return $this->aTags;
    }


    /**
     *
     *
     * @see wwwViewquestions::makeRecentTags()
     * @return \Lampcms\Controllers\Unanswered
     */
    protected function makeRecentTags()
    {

        /**
         * If user is logged in AND
         * has 'followed tags' then don't use
         * cache and instead do this:
         * get array of recent tags, sort in a way
         * than user's tags are on top and then render
         * This way user's tags will always be on top
         * at a cost of couple of milliseconds we get
         * a nice personalization that does increase
         * the click rate!
         * */
        $aUserTags = $this->Registry->Viewer['a_f_t'];
        if (!empty($aUserTags)) {
            $s = $this->getSortedRecentTags($aUserTags, 'unanswered');
        } else {
            $s = $this->Registry->Cache->qunanswered;
        }

        $tags                    = \tplBoxrecent::parse(array('tags'  => $s,
                                                              'title' => '@@Unanswered tags@@'));
        $this->aPageVars['tags'] = $tags;

        return $this;
    }


    protected function makeCounterBlock()
    {
        $this->aPageVars['topRight'] = \tplCounterblock::parse(array($this->count, $this->title, $this->counterTaggedText), false);

        return $this;
    }


    /**
     * Make html of the 'Follow this tag' button
     * Check is user is already following it, then
     * shows 'Following' button
     * Sets the value of $this->aPageVars['side']
     * to be the div with follow button
     *
     * @return object $this
     *
     */
    protected function makeFollowTagButton()
    {

        /**
         * Only show the follow tag button
         * if viewing page about only one tag
         * a page about multiple tags
         * will not show follow tag because
         * it's not clear which tag to follow
         *
         */
        if (1 === \count($this->aTags)) {
            $tag = $this->aTags[0];
            d('tag: ' . $tag);

            $aFollowed = $this->Registry->Viewer['a_f_t'];
            d('$aFollowed: ' . \print_r($aFollowed, 1));

            $aVars = array(
                'id'    => $tag,
                'icon'  => 'cplus',
                'label' => '@@Follow this tag@@',
                'class' => 'follow',
                'type'  => 't',
                'title' => '@@Follow this tag to be notified when new questions are added@@'
            );

            if (in_array($tag, $aFollowed)) {
                $aVars['label'] = '@@Following@@';
                $aVars['class'] = 'following';
                $aVars['icon']  = 'check';
                $aVars['title'] = '@@You are following this tag@@';
            }

            $this->aPageVars['side'] = '<div class="fr cb w90 lg rounded3 pl10 mb10"><div class="follow_wrap">' . \tplFollowButton::parse($aVars, false) . '</div></div>';

            $this->addTagFollowers();

        }

        return $this;
    }
}
