<?php
/**
 *
 * License, TERMS and CONDITIONS
 *
 * This software is lisensed under the GNU LESSER GENERAL PUBLIC LICENSE (LGPL) version 3
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
 *    but it must not be hidden by style attibutes
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

use Lampcms\WebPage;
use Lampcms\Request;

/**
 * Controller for creating a page
 * with the "Edit" form
 * to edit question or answer
 * It creates the form object
 * and adds form to the page template
 *
 * @author Dmitri Snytkine
 *
 */
class Edit extends WebPage
{

    /**
     * Name of collection that this resource
     * belongs to. This is either QUESTIONS or ANSWERS
     *
     * @var string
     */
    protected $collection;


    /**
     * Resource being edited - either Question or Answer object
     *
     * @var object
     */
    protected $Resource;


    /**
     * Form object
     *
     * @var object
     */
    protected $Form;

    /**
     * Resource type (q for question, a for answer)
     *
     * @var string
     */
    protected $rtype;

    /**
     * Resource ID
     *
     * @var int
     */
    protected $rid;

    protected function main()
    {

        $this->getResource()
            ->configureEditor()
            ->checkPermission()
            ->makeForm()
            ->setForm()
            ->setMemo();
    }

    /**
     * Init values of $this->rid and $this->rtype based
     * on type of request (POST OR GET) will use Router or Request
     *
     * @return object $this
     */
    protected function initRequestParams()
    {
        if (Request::isPost()) {
            $this->rtype = $this->Request['rtype'];
            $this->rid   = (int)$this->Request['rid'];
        } else {
            $this->rid   = $this->Registry->Router->getNumber(1);
            $this->rtype = $this->Registry->Router->getSegment(2, 's', 'a');
        }

        return $this;
    }


    /**
     *
     *
     * @return object $this
     */
    protected function setMemo()
    {
        $memo = '<strong>@@How to Edit@@:</strong>
		<ul>
		<li>@@Fix grammatical or spelling errors@@</li>
		<li>@@Clarify meaning without changing it@@</li>
		<li>@@Add related resources or links@@</li>
		</ul>';

        $this->aPageVars['qheader'] = '<div class="memo">' . $memo . '</div>';

        return $this;
    }


    protected function makeForm()
    {
        d('cp');
        $this->Form = new \Lampcms\Forms\Edit($this->Registry);
        $body       = $this->Resource['b'];

        /**
         * <pre rel="codepreview" class="xml">
         */
        d('body: ' . $body);
        $body              = \str_replace('rel="code"', 'alt="codepreview"', $body);
        $this->Form->qbody = $body;
        $this->Form->id    = $this->Resource->getResourceId();
        $this->Form->rtype = $this->rtype;

        if ('ANSWERS' === $this->collection) {
            $this->Form->hidden = ' hidden';
        } else {
            $this->Form->title    = $this->Resource['title'];
            $this->Form->id_title = $this->Resource['id_title'];
            $minTitle             = $this->Registry->Ini->MIN_TITLE_CHARS;
            $this->Form->addValidator('title', function($val) use ($minTitle)
            {

                if (\mb_strlen($val) < $minTitle) {
                    $err = 'Title must contain at least %s letters';
                    return \sprintf($err, $minTitle);
                }

                return true;
            });
        }

        return $this;
    }


    /**
     * Add html of the form
     * to page body
     *
     * @return object $this
     */
    protected function setForm()
    {
        $form                    = $this->Form->getForm();
        $this->aPageVars['body'] = $form;

        return $this;
    }


    /**
     * Create object of type Question or Answer
     *
     * @throws \Lampcms\Exception
     * @return \Lampcms\Controllers\object $this
     */
    protected function getResource()
    {
        $this->initRequestParams();
        $this->collection = ('q' == $this->rtype) ? 'QUESTIONS' : 'ANSWERS';
        $this->permission = ('QUESTIONS' === $this->collection) ? 'edit_question' : 'edit_answer';

        d('type: ' . $this->collection);
        $coll = $this->Registry->Mongo->getCollection($this->collection);
        $a    = $coll->findOne(array('_id' => (int)$this->rid));
        d('a: ' . \print_r($a, 1));

        if (empty($a)) {

            throw new \Lampcms\Exception('@@Item not found@@');
        }

        $class = ('QUESTIONS' === $this->collection) ? '\\Lampcms\\Question' : '\\Lampcms\\Answer';

        $this->Resource = new $class($this->Registry, $a);

        return $this;
    }


    /**
     * Edits can be done by owner of resource
     * OR by someone who has necessary permission
     *
     * @return object $this
     * @throws checkAccessPermission() will throw exception
     * if Viewer does not have required permission
     */
    protected function checkPermission()
    {
        if (!\Lampcms\isOwner($this->Registry->Viewer, $this->Resource)) {
            if ($this->Registry->Ini->POINTS->EDIT > $this->Registry->Viewer->getReputation()) {
                $this->checkAccessPermission();
            }
        }

        return $this;
    }

}
