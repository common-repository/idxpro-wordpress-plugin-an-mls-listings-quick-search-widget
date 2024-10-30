// Docu : http://wiki.moxiecode.com/index.php/TinyMCE:Create_plugin/3.x#Creating_your_own_plugins

// the AR number is available via a global js variable "idxpro_account_id"
//alert(idxpro_account_id);

(function() {
	
	var each = tinymce.each;
	var pl_url = '';

	// Load plugin specific language pack
	tinymce.PluginManager.requireLangPack('idxpro_embed');

	tinymce.create('tinymce.plugins.idxpro_embed', {
		/**
		 * Initializes the plugin, this will be executed after the plugin has been created.
		 * This call is done before the editor instance has finished it's initialization so use the onInit event
		 * of the editor instance to intercept that event.
		 *
		 * @param {tinymce.Editor} ed Editor instance that the plugin is initialized in.
		 * @param {string} url Absolute URL to where the plugin is located.
		 */
		init : function(ed, url) {
			// Register the command so that it can be invoked by using tinyMCE.activeEditor.execCommand('mceidxpro_embed');
			ed.addCommand('mceidxpro_embed', function() {
				ed.windowManager.open({
					file : url + '/window.php',
					width : 360 + ed.getLang('idxpro_embed.delta_width', 0),
					height : 300 + ed.getLang('idxpro_embed.delta_height', 0),
					inline : 1
				}, {
					plugin_url : url // Plugin absolute URL
				});
			});

			pl_url = url;

			// Register idxpro_embed button
			/*ed.addButton('idxpro_embed', {
				title : 'Embed IDXPro Application or Widget',
				//cmd : 'mceidxpro_embed',
				image : url + '/idxpro_button.png',
				onclick : function() {
					//idPattern = /(?:(?:[^v]+)+v.)?([^&=]{11})(?=&|$)/;
					//var vidId = prompt("YouTube Video", "Enter the id or url for your video");
					//var m = idPattern.exec(vidId);
					var widget_id = 0;
					
					//if (m != null && m != 'undefined')
						//ed.execCommand('mceInsertContent', false, "[idxpro account='" + idxpro_account_id + "']");
						ed.execCommand('mceInsertContent', false, "[idxpro]");
				}
			});*/

			// Add a node change handler, selects the button in the UI when a image is selected
			ed.onNodeChange.add(function(ed, cm, n) {
				cm.setActive('idxpro_embed', n.nodeName == 'IMG');
			});
		},

		/**
		 * Creates control instances based in the incoming name. This method is normally not
		 * needed since the addButton method of the tinymce.Editor class is an easier way of adding buttons
		 * but you sometimes need to create more complex controls like listboxes, split buttons etc then this
		 * method can be used to create those.
		 *
		 * @param {String} n Name of the control to create.
		 * @param {tinymce.ControlManager} cm Control manager to use inorder to create new control.
		 * @return {tinymce.ui.Control} New control instance or null if no control was created.
		 */
		createControl : function(n, cm) {
			switch (n) {
				case 'idxpro_embed':
				{
		    				c = cm.createSplitButton(n, {title : 'IDXPro', image : pl_url + '/idxpro_button.png'});
		    				c.onRenderMenu.add( function(c, m) {
		    					each( idx_widget_buttons, function( v, k ) { m.add( { title : v.title, onclick : function() { tinyMCE.activeEditor.execCommand( 'mceInsertContent', false, v.value);  }  } ).setDisabled( 0 ); } );
		    				} );
		    	    return c;
				}
			}
			return null;
		},

		/**
		 * Returns information about the plugin as a name/value array.
		 * The current keys are longname, author, authorurl, infourl and version.
		 *
		 * @return {Object} Name/value array containing information about the plugin.
		 */
		getInfo : function() {
			return {
				longname : 'IDXPro Plugin',
				author : 'iHOUSE',
				authorurl : 'http://www.ihouseweb.com',
				infourl : 'http://www.idxpro.com',
				version : "1.0"
			};
		}
	});

	// Register plugin
	tinymce.PluginManager.add('idxpro_embed', tinymce.plugins.idxpro_embed);
})();