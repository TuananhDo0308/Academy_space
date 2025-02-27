<?php
/**
  * @package      Joomdle
  * @copyright    Qontori Pte Ltd
  * @license      http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
  */

defined('_JEXEC') or die('Restricted access');

JToolBarHelper::title(JText::_('COM_JOOMDLE_JOOMDLE'), 'generic.png');

?>
<style>
.cpanel{padding-left:25px;text-align:left}

.cpanel div.icon ,
#cpanel div.icon {
    margin-left: 5px;
    float: right;
}

.cpanel div.icon a ,
#cpanel div.icon a {
    float: right;
}

.cpanel img ,
#cpanel img {
    padding: 10px 0;
    margin: 0 auto;
}

div.cpanel-icons {
    float: right;
}

div.cpanel-component {
    float: left;
}

.panel_btn {
    display: inline-block;
    *display: inline;
    *zoom: 1;
    padding: 4px 12px;
    margin-bottom: 0;
    font-size: 13px;
    line-height: 18px;
    text-align: center;
    vertical-align: middle;
    cursor: pointer;
    background-color: #f3f3f3;
    color: #333;
    border: 1px solid #b3b3b3;
    -webkit-border-radius: 3px;
    -moz-border-radius: 3px;
    border-radius: 3px;
    box-shadow: 0 1px 2px rgba(0,0,0,0.05);
}
</style>

       <table class="adminlist">

             <tbody>
             <tr>
            <td width="55%" valign="top">
            <div id="cpanel">
            <table>
            <tr>
                <td>
            <?php
            $link = 'index.php?option=com_joomdle&amp;view=config';
            $this->showButton( $link, 'config.png', JText::_( 'COM_JOOMDLE_CONFIGURATION' ) );
            ?>
                </td>
                <td>
            <?php
            $link = 'index.php?option=com_joomdle&amp;view=users';
            $this->showButton( $link, 'users.png', JText::_( 'COM_JOOMDLE_USERS' ) );
            ?>
                </td>
                <td>
            <?php
            $link = 'index.php?option=com_joomdle&amp;view=mappings';
            $this->showButton( $link, 'mappings.png', JText::_( 'COM_JOOMDLE_DATA_MAPPINGS' ) );
            ?>
                </td>
            </tr>
            <tr>
                <td>
            <?php
            $link = 'index.php?option=com_joomdle&amp;view=customprofiletypes';
            $this->showButton( $link, 'profiletypes.png', JText::_( 'COM_JOOMDLE_CUSTOM_PROFILETYPES' ) );
            ?>
                </td>
                <td>
            <?php
            $link = 'index.php?option=com_joomdle&amp;view=shop';
            $this->showButton( $link, 'vmart.png', JText::_( 'COM_JOOMDLE_SHOP_INTEGRATION' ) );
            ?>
                </td>
                <td>
            <?php
            $link = 'index.php?option=com_joomdle&amp;view=mailinglist';
            $this->showButton( $link, 'lists.png', JText::_( 'COM_JOOMDLE_MAILING_LIST_INTEGRATION' ) );
            ?>
                </td>
            </tr>
            <tr>
                <td>
            <?php
            $link = 'index.php?option=com_joomdle&amp;view=courseapplications';
            $this->showButton( $link, 'applications.png', JText::_( 'COM_JOOMDLE_APPLICATIONS' ) );
            ?>
                </td>
                <td>
            <?php
    //        $link = 'index.php?option=com_joomdle&amp;view=forums';
    //        $this->showButton( $link, 'forum.png', JText::_( 'COM_JOOMDLE_FORUMS' ) );

    //        echo '<div style="clear: both;" />';
            $link = 'index.php?option=com_joomdle&amp;view=check';
            $this->showButton( $link, 'info.png', JText::_( 'COM_JOOMDLE_SYSTEM_CHECK' ) );
            ?>
                </td>
            </tr>
            </table>
            </div>
            </td>
            <td width="45%" valign="top">
            <div style="width: 100%">
<?php
            $title = JText::_("COM_JOOMDLE_ABOUT");
            $options = array ('active' => 'about');
            echo JHTML :: _ ('bootstrap.startTabSet', 'myTab', $options);
            echo JHTML :: _ ('bootstrap.addTab', 'myTab', 'about', $title);

            $renderer = 'renderAbout';
            echo $this->$renderer();
 
            echo JHTML :: _ ('bootstrap.endTabSet');
?>

            </div>
            </td>
        </tr>
             </tbody>
       </table>
        <input type="hidden" name="option" value="com_joomdle"/>
        <input type="hidden" name="task" value=""/>
        <input type="hidden" name="boxchecked" value="0"/>
        <input type="hidden" name="hidemainmenu" value="0"/>

        <?php echo JHTML::_( 'form.token' ); ?>

