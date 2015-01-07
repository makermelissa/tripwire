<?php
$user = $this->session->userdata('user');
if (empty($user)) {
	echo form_open(base_url() . 'home/login'); ?>
    <table id="login-table" align="right">
        <tr>
            <td>&nbsp;</td>
            <td class="forgot" align="left"><a href="/home/forgotpw">I Forgot My Password</a></td>
            <td align="center"><a href="/apply">Apply</a></td>
        </tr>
        <tr>
            <td align="right">Email:</td>
            <td><?php echo form_input('email', set_value('email')); ?></td>
            <td rowspan="2" class="vcenter"><input type="submit" value="Log In" alt="Log In" title="Log In"></td>
        </tr>
        <tr>    
            <td align="right">Password:</td>
            <td><?php echo form_password('password', ''); ?></td>
        </tr>
        <tr class="error-msgs">
            <td colspan="3"><?php echo form_error('email').form_error('password'); ?></td>
        </tr>
    </table>
<?php 
} else {
	echo form_open(base_url() . 'home/logout');
?>
   <table id="login-table" align="right">
        <tr>
            <td>Welcome, <?php echo anchor('/'.$user->user_level, $user->firstname); ?>.</td>
        </tr>
        <tr>    
            <td><input type="submit" value="Log Out" alt="Log Out" title="Log Out"></td>
        </tr>
    </table>
<?php 
}
echo form_close();
?>
