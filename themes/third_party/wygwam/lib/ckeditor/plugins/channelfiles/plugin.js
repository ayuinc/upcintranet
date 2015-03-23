CKEDITOR.plugins.add('channelfiles',
{
	requires : ['dialog'],
	
	init: function(editor)
	{
		// Only if CI is there
		if (typeof(ChannelFiles) == 'object')
		{
			// Plugin Name
			var pluginName = 'channelfiles';
	    	
			// Add Dialog JS
			CKEDITOR.dialog.add(pluginName, this.path + 'dialogs/channelfiles.js');
	        
			// Add Command?
			editor.addCommand(pluginName, new CKEDITOR.dialogCommand(pluginName));
	        
			// Add Toolbar Button
			editor.ui.addButton('ChannelFiles',
			{
				label: 'Add/Edit Files',
				icon: this.path + 'cf_button.png',
				command: pluginName
			});
		}
	}
});
// http://www.voofie.com/content/2/ckeditor-plugin-development/