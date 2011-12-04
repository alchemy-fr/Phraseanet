
function FileProgress(file, targetID) {
	this.fileProgressID = file.id;

	this.opacity = 100;
	this.height = 0;
	

	this.fileProgressWrapper = $('#'+this.fileProgressID);
	this.fileProgressCanceller = $('#'+this.fileProgressID+' a.progressCancel');
	this.fileProgressContainer = $('#'+this.fileProgressID+' div.progressContainer');
	this.fileProgressStatus = $('#'+this.fileProgressID+' div.progressBarStatus');
	this.fileProgressBar = $('#'+this.fileProgressID+' div.progressBarInProgress');

	if (this.fileProgressWrapper.length === 0) {
		
		var elem = 	'<li class="progressWrapper" id="'+this.fileProgressID+'">'+
								'<div class="progressContainer">'+
									'<a class="progressCancel" href="#" style="visibility:hidden"> </a>'+
									'<div class="progressName">'+file.name+'</div>'+
									'<div class="progressBarStatus">&nbsp;</div>'+
									'<div class="progressBarInProgress"></div>'+
								'</div>'+
							'</li>';
		
		$('#'+targetID).append(elem);
		
		this.fileProgressWrapper = $('#'+this.fileProgressID);

		$.each($('#'+this.fileProgressID+' .slider_status'),function(){
			activeSliders($(this));
			swfu.addFileParam(file.id,'status[4]',$(this).slider('value'));
		});
	} else {
		this.reset();
	}

	this.fileProgressCanceller = $('#'+this.fileProgressID+' a.progressCancel');
	this.fileProgressContainer = $('#'+this.fileProgressID+' div.progressContainer');
	this.fileProgressStatus = $('#'+this.fileProgressID+' div.progressBarStatus');
	this.fileProgressBar = $('#'+this.fileProgressID+' div.progressBarInProgress');
	this.height = this.fileProgressWrapper.offsetHeight;
}

FileProgress.prototype.reset = function () {
	this.fileProgressContainer.removeClass('green blue red');
	this.fileProgressStatus.html('&nbsp;');
	this.fileProgressBar.css('width','0%');
	this.fileProgressWrapper.show();
};

FileProgress.prototype.setProgress = function (percentage) {
	this.fileProgressContainer.addClass('green');
	this.fileProgressBar.css('width',percentage+'%');
	this.fileProgressWrapper.show();
};
FileProgress.prototype.setComplete = function () {
	this.fileProgressWrapper.addClass('done');
	this.fileProgressContainer.addClass('green');
	this.fileProgressBar.addClass('complete').css('width','auto');
};
FileProgress.prototype.setQuarantine = function () {
	this.fileProgressWrapper.addClass('done');
	this.fileProgressContainer.addClass('orange');
	this.fileProgressContainer.addClass('quarantine');
	this.fileProgressBar.addClass('complete').css('width','auto');
};
FileProgress.prototype.setError = function () {
	this.fileProgressWrapper.addClass('done');
	this.fileProgressContainer.removeClass('green blue').addClass('red');
	this.fileProgressBar.removeClass('progressBarInProgress').addClass('progressBarError').css('width','auto');
};
FileProgress.prototype.setCancelled = function () {
	this.fileProgressWrapper.addClass('done');
	this.fileProgressContainer.removeClass('green blue red');
	this.fileProgressBar.removeClass('progressBarInProgress').addClass('progressBarError').css('width','auto');
	this.fileProgressWrapper.fadeOut();
};
FileProgress.prototype.setStatus = function (status) {
	this.fileProgressStatus.html(status);
};

// Show/Hide the cancel button
FileProgress.prototype.toggleCancel = function (show, swfUploadInstance) {
	this.fileProgressCanceller.css('visibility',(show ? "visible" : "hidden"));
	if (swfUploadInstance) {
		var fileID = this.fileProgressID;
		this.fileProgressCanceller.bind('click', function () {
			swfUploadInstance.cancelUpload(fileID);
			return false;
		});
	}
};
