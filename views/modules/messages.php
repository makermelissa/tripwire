<?php 
if ($this->session->flashdata('error_message')) { 
	$messages['error'] = array('text'=>$this->session->flashdata('error_message'), 
									'title'=>$this->session->flashdata('message_title'),
									'button'=>$this->session->flashdata('message_ok_label'));
}
if ($this->session->flashdata('success_message')) { 
	$messages['success'] = array('text'=>$this->session->flashdata('success_message'), 
									'title'=>$this->session->flashdata('message_title'),
									'button'=>$this->session->flashdata('message_ok_label'));
}
if ($this->session->flashdata('general_message')) { 
	$messages['general'] = array('text'=>$this->session->flashdata('general_message'), 
									'title'=>$this->session->flashdata('message_title'),
									'button'=>$this->session->flashdata('message_ok_label'));
}

if (!is_null($messages['error'])) {
	$data = $messages['error'];
?>
                <script language="javascript">
                $(document).ready(function() {
                   $("#error_message_wrapper").dialog({
						resizable: false,
						autoOpen: false,
						modal: true,
						buttons: {
							<?php echo $data['button'] == 'Ok' ? 'Ok' : '"'.$data['button'].'"'?>: function() {
								$(this).dialog("close");
							}
						}
                   });
                   $("#error_message_wrapper").dialog("open");
                });
                </script>
                <div id="error_message_wrapper" class="message" style="display: none;" title="<?php echo $data['title']; ?>"><?php echo $data['text']; ?></div>
<?php 
}
if (!is_null($messages['success'])) { 	
	$data = $messages['success'];
?>
                <script language="javascript">
                $(document).ready(function() {
                   $("#success_message_wrapper").dialog({
						resizable: false,
						autoOpen: false,
						modal: true,
						buttons: {
							<?php echo $data['button'] == 'Ok' ? 'Ok' : '"'.$data['button'].'"'?>: function() {
								$(this).dialog("close");
							}
						}
                   });
                   $("#success_message_wrapper").dialog("open");
                });
				
                </script>
                <div id="success_message_wrapper" class="message" style="display: none;" title="<?php echo $data['title']; ?>"><?php echo $data['text']; ?></div>
<?php 
}
if (!is_null($messages['general'])) { 
	$data = $messages['general'];
?>
                <script language="javascript">
                $(document).ready(function() {
                   $("#general_message_wrapper").dialog({
						resizable: false,
						autoOpen: false,
						modal: true,
						buttons: {
							<?php echo $data['button'] == 'Ok' ? 'Ok' : '"'.$data['button'].'"'?>: function() {
								$(this).dialog("close");
							}
						}
                   });
                   $("#general_message_wrapper").dialog("open");
                });
                </script>
                <div id="general_message_wrapper" class="message" style="display: none;" title="<?php echo $data['title']; ?>"><?php echo $data['text']; ?></div>
<?php 
} ?>

<div id="js_message_wrapper" class="message" title="" style="display: none"></div>