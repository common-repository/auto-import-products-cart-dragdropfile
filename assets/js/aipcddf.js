(function( $ ){
	'use strict';

	$( document ).ready(function(){

		// init DropzoneJS
		var myDropzone = new Dropzone("#auto-import-products-cart-dragdropfile-uploder", {
			
			url: atakanau_woo_cart_dropfile_cntrl.upload_file,
			paramName: "auto-import-products-cart-dragdropfile-file", // name of file field
			acceptedFiles: 'text/csv,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.oasis.opendocument.spreadsheet', // accepted file types
			maxFilesize: 2, // MB
			addRemoveLinks: false,
			// append wp_nonce to POST values
			init: function (){
				this.on("sending", function (file, xhr, formData){
					$( "#auto-import-products-cart-dragdropfile-log" ).text('');
					formData.append("auto-import-products-cart-dragdropfile-nonce", $('#auto-import-products-cart-dragdropfile-nonce').val());
				});
			},
			// success file upload handling
			success: function (file, response){
				// handle your response object
				// console.log(response.status);
				if( 'success' == response.status ){
					var nonce = $('#auto-import-products-cart-dragdropfile-nonce').val()
					if( continue_check(response.counted, 1) ){
						ajax_import(atakanau_woo_cart_dropfile_cntrl.import_file,nonce,response.filename,response.column_sku,response.column_qty,response.counted,1)
					}
				}
				else if( 'error' == response.status ){
					$( "#auto-import-products-cart-dragdropfile-log" ).text(response.message);
				}
				
				file.previewElement.classList.add("dz-success");
				file['attachment_id'] = response.attachment_id; // adding uploaded ID to file object
			},

			// error while handling file upload
			error: function (file,response){
				file.previewElement.classList.add("dz-error");
			},

		});
		myDropzone.on("complete", function(file){
			myDropzone.removeFile(file);
		});
		$( "div#auto-import-products-cart-dragdropfile-uploder" ).addClass("dropzone");
		
		function continue_check(counted, step){
			var iscontinue = false;
			if(counted > step){
				iscontinue = true;
			}
			return iscontinue;
		}
		function progress_bar(total, step) {
			var bar = "";
			var percentage = Math.floor((step / total) * 100);
			for (var i = 0; i < Math.floor(percentage / 5); i++) {
				bar += "█";
			}
			for (var i = 0; i < 20 - Math.floor(percentage / 5); i++) {
				bar += "░";
			}
			$( "#auto-import-products-cart-dragdropfile-log" ).html("<code>" + bar + " " + percentage + "% ( " +  + step + " / " + total + " )</code>");
		}
		function ajax_import(url, nonce, filename, col_sku, col_qty, counted, step){
			step = typeof step !== "undefined" ? step : 1;
			progress_bar(counted, step)
			var jqxhr = $.ajax({
					 url: url
					,method: "POST"
					,data: {
						'auto-import-products-cart-dragdropfile-nonce' : nonce
						,'filename'		: filename
						,'column_sku'	: col_sku
						,'column_qty'	: col_qty
						,'counted'		: counted
						,'step'			: step
					}
					,dataType: "json"
				})
				.done(function (response){
					// console.log(response, continue_check(response.counted, response.step) );
					if( continue_check(response.counted, response.step) ){
						setTimeout(function () {
							ajax_import(url, nonce, filename, col_sku, col_qty, counted, step+1);
						}, 333);
					}
				})
				.fail(function (){
					alert("error");
				})
				.always(function (){
				});
		}
	});

})( jQuery );
