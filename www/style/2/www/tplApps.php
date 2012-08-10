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

/**
 * Template for generating view
 * of one app in the Viewapps page
 * This block will include icon,
 * name of app linked to viewapp page
 * and "Edit app" link linked to
 * /index.php?a=editapp&app_id=%d
 *
 * @author Dmitri Snytkine
 *
 */
class tplApps extends Lampcms\Template\Fast
{
    /**
     * If App does not have 'icon'
     * then fall back to default generic
     * app icon image
     *
     * @param array $a
     */
    protected static function func(&$a)
    {
        if (empty($a['icon'])) {
            $a['icon'] = '{_IMAGE_SITE_}{_DIR_}/images/app2.png';
        } else {
            $a['icon'] = '{_AVATAR_IMG_SITE_}{_DIR_}' . \Lampcms\PATH_WWW_IMG_AVATAR_SQUARE . $a['icon'];
        }

        if (array_key_exists("app_name", $a)) {
            $a['app_name'] = \trim($a['app_name']);
        }
    }


    protected static $vars = array(
        '_id' => '0', //1
        'app_name' => '', //2
        'about' => '', //3
        'icon' => '' //4
    );


    protected static $tpl = '
	<div class="cb fl mb10 mt10">
		<div class="fl">
			<a href="{_WEB_ROOT_}/{_viewapp_}/%1$s">
				<img src="%4$s" width="72px" height="72px" class="appicon"></img>
			</a>
		</div>
		<div class="fl ml10">
			<a href="{_WEB_ROOT_}/{_viewapp_}/%1$s" class="bold">%2$s</a>
			<br>
			<span class="fl mb5 pre">%3$s</span><br>
			<a href="{_WEB_ROOT_}/{_editapp_}/%1$s">@@Edit Details@@ &gt;&gt;</a>
		</div>
	</div>
	';
}