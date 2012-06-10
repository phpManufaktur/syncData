{*
 * syncData
 *
 * @author Ralf Hertsch (ralf.hertsch@phpmanufaktur.de)
 * @link http://phpmanufaktur.de
 * @copyright 2011
 * @license GNU GPL (http://www.gnu.org/licenses/gpl.html)
 * @version $Id: backend.restore.start.lte 15 2011-08-26 14:01:12Z phpmanufaktur $
 *}
<div id="sync_data_backup">
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
        <td>{$restore.label}</td>
        <td>
          <select name="{$restore.name}">
            {foreach $restore.options option}
            <option value="{$option.value}">{$option.text}</option>
            {/foreach}
          </select>
        </td>
        <td>{$restore.hint}</td>
      </tr>
      <tr><td colspan="3">&nbsp;</td></tr>
      <tr>
        <td>&nbsp;</td>
        <td colspan="2">
          <input type="submit" value="{$form.btn.ok}" />
        </td>
      </tr>
    </table>
  </form>
  </div>
</div>
