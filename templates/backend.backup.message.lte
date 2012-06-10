{*
 * syncData
 *
 * @author Ralf Hertsch (ralf.hertsch@phpmanufaktur.de)
 * @link http://phpmanufaktur.de
 * @copyright 2011
 * @license GNU GPL (http://www.gnu.org/licenses/gpl.html)
 * @version $Id: backend.backup.message.lte 15 2011-08-26 14:01:12Z phpmanufaktur $
 *}
   
<div id="sync_data_backup">
  {if isset($text_process)}
  <div id="process">
    <div id="process_left">
      <img src="{$img_url}loading.gif" widht="100" height="100" alt="running..." />
    </div>
    <div id="process_right">
      {$text_process}
    </div>
  </div>
  {/if}
  <div id="backup_form">
    <form name="{$form.name}" action="{$form.link}" method="post" {if isset($text_process)}onsubmit="document.getElementById('process').style.display='block'; document.getElementById('backup_form').style.display='none';return true;"{/if}>
      <input type="hidden" name="{$form.action.name}" value="{$form.action.value}" />
      <input type="hidden" name="{$job.name}" value="{$job.value}" />
      <h2>{$head}</h2>
      <div class="{if $is_intro == 1}intro{else}message{/if}">{$intro}</div>
      <div>
        {if isset($form.btn.abort)}<input type="button" value="{$form.btn.abort}" onclick="javascript: window.location = '{$form.link}'; return false;" /> {/if}<input type="submit" value="{$form.btn.ok}" />
      </div>
    </form>
  </div>
</div>
