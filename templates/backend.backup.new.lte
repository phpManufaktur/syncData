{*
 * syncData
 *
 * @author Ralf Hertsch (ralf.hertsch@phpmanufaktur.de)
 * @link http://phpmanufaktur.de
 * @copyright 2011
 * @license GNU GPL (http://www.gnu.org/licenses/gpl.html)
 * @version $Id: backend.backup.new.lte 15 2011-08-26 14:01:12Z phpmanufaktur $
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
  <form name="{$form.name}" action="{$form.link}" method="post" onsubmit="document.getElementById('process').style.display='block'; document.getElementById('backup_form').style.display='none';return true;">
    <input type="hidden" name="{$form.action.name}" value="{$form.action.value}" />
    <h2>{$head}</h2>
    <div id="intro" class="{if $is_intro == 1}intro{else}message{/if}">{$intro}</div>
    <table width="100%">
      <colgroup>
        <col width="200" /> 
        <col width="*" />
        <col width="300" />
      </colgroup>
      <tr>
        <td>{$backup_type.label}</td>
        <td>
          <select name="{$backup_type.name}">
            {foreach $backup_type.options option}
            <option value="{$option.value}"{if $option.selected == 1} selected="selected"{/if} >{$option.text}</option>
            {/foreach}
          </select>
        </td>
        <td>{$backup_type.hint}</td>
      </tr>
      <tr>
        <td>{$archive_name.label}</td>
        <td><input type="text" name="{$archive_name.name}" value="{$archive_name.value}" /></td>
        <td>{$archive_name.hint}</td>
      </tr>
      <tr><td colspan="3">&nbsp;</td></tr>
      <tr>
        <td>&nbsp;</td>
        <td colspan="2"><input type="submit" value="{$form.btn.ok}" /></td>
      </tr>
    </table>
  </form>
  </div>
</div>
