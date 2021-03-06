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


class tplFormapiclient extends Lampcms\Template\Fast
{

    /**
     * Preserve state of "selected" value
     * of radio button 'app_type'
     *
     *
     * @param array $a
     */
    protected static function func(&$a)
    {

        if ('b' === $a['app_type']) {
            $a['selected_b'] = 'checked';
        } elseif ('c' === $a['app_type']) {
            $a['selected_c'] = 'checked';
        }
    }


    protected static $vars = array(
        'token' => '', // 1
        'formTitle' => '@@Register an Application@@', //2
        'app_id' => '0', //3
        'app_name' => '', //4
        'app_name_l' => '@@Application Name@@', //5
        'appsite' => '', //6
        'appsite_l' => '@@Application Website@@', //7
        'company' => '', // 8
        'company_l' => '@@Organization@@', //9
        'about' => '', //10
        'about_l' => '@@Description@@', // 11
        'submit' => '@@Save Application Settings@@', // 12
        'selected_b' => '', //13
        'selected_c' => '', //14
        'app_type_l' => '@@Application type@@', //15
        'app_type_d' => '', //16
        'formError' => '', //17
        'maxUploadSize' => '700000', //18
        'icon_image' => '{_IMAGE_SITE_}{_DIR_}/images/app2.png', //19
        'icon_e' => '', //20
        'app_name_e' => '', //21
        'captcha' => '', //22
        'app_type' => '', // 23
        'app_type_e' => '', // 24
        'required_l' => '@@Required@@', // 25
        'icon' => '' //26 Need this or Form class will complain about field does not exist
    );

    protected static $tpl = '
	<form name="epForm" method="POST" action="{_WEB_ROOT_}/" enctype="multipart/form-data" accept-charset="utf-8">
		<input type="hidden" name="a" value="editapp">
		<input type="hidden" name="app_id" value="%3$s">	
		<input type="hidden" name="token" value="%1$s">
<div id="tools" class="form_wrap">
<h1>%2$s</h1>
<div class="form_error">%17$s</div>

<table class="larger vtop eprofile">
<tr>
	<td>%5$s</td>
	<td><input tabindex="10" type="text" size="30" id="id_app" name="app_name" value="%4$s">
	<span class="f_err">%21$s</span></td>
</tr>
<tr><td>%7$s</td><td><input tabindex="11" type="text" size="30" id="id_appsite" name="appsite" value="%6$s"></td></tr>
<tr><td>%9$s</td><td><input tabindex="12" type="text" size="30" id="id_company" name="company" value="%8$s"></td></tr>
<tr><td>%15$s<br><strong>(*) %25$s</strong></td>
<td>
<input tabindex="14" type="radio" value="b" name="app_type" %13$s> Browser 
<input tabindex="13" type="radio" value="c" name="app_type" %14$s> Client <br>
<p>Does your application run in a Web Browser or a Desktop Client?<br> 
              <small>Browser uses a Callback URL to return to your App after successful authentication.<br> 
              Client prompts your user to return to your application after approving access.</small></p> 
              <span class="f_err">%24$s</span>
</td>
</tr>

<tr><td>%11$s</td><td><textarea tabindex="18" id="id_about" rows="3" cols="40" class="com_body" name="about">%10$s</textarea>
   <br><span class="smaller">Maximum 300 characters. No HTML</span>             
 </td></tr>
 <tr>
 	<td>Application icon
 	<div class="fl cb">
 		<img src="%19$s" width="72px" height="72px" alt="App Logo" class="fl img_avatar">
 	</div>
 	</td>
 	<td>
 	<div class="fl cb">
 	<input type="hidden" name="MAX_FILE_SIZE" value="%18$s">
 	<input tabindex="19" type="file" size="30" id="id_icon" name="icon">
 	<span class="f_err">%20$s</span>
  	<p class="smaller">Maximum size of 700k.  JPG, GIF, PNG.<br>Your can upload this later!</p> 
  	</div>
  	</td>
 </tr>
 <!-- captcha -->
    <tr>
	<td></td>
	<td><div class="form_el">
			%22$s
         </div>
    </td>
    </tr>
  <!-- //captcha -->
<tr>
	<td></td>
	<td><div class="form_el">
            <input tabindex="20" id="dostuff" name="submit" type="submit" value="%12$s" class="btn btn-m"> 
         </div>
    </td>
 </tr>
</table>
</div>
</form>
	';
}
