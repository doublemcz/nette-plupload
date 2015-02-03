;
(function ($) {
	var NettePlupload = function (element) {
		if (element.pluploadInitialized) {
			return;
		}

		this.pluploadInitialized = true;
		this.$element = $(element);
		var nettePlupload = this;
		element.getNettePlupload = function() {
			return nettePlupload;
		}

		this.$dropZone = this.$element.find('.np-drop-zone');
		this.options = this.$element.data('uploader-options');
		this.queue = [];
		this.isUploaded = false;
		this.onFormSubmitBlock = null;
		this.onUploadComplete = null;
		this.$form = this.$element.closest('form');;
		this.initForm();
		this.initPlupload();
	};

	NettePlupload.prototype.initForm = function () {
		var nettePlupload = this;
		if (this.$form.length) {
			this.$form.on('submit', function (e) {
				if (!nettePlupload.isUploaded) {
					nettePlupload.uploader.start();
					e.preventDefault();
					if (nettePlupload.onFormSubmitBlock) {
						nettePlupload.onFormSubmitBlock(this);
					}
				}
			});
		}
	}

	NettePlupload.prototype.addFiles = function (files) {
		var nettePlupload = this;
		$.each(files, function (key, data) {
			nettePlupload.queue.push(new FileInQueue(data, nettePlupload));
		});
	};

	NettePlupload.prototype.getFileInQueueById = function (id) {
		for (var key in this.queue) {
			if (this.queue[key].data.id == id) {
				return this.queue[key];
			}
		}
	}

	NettePlupload.prototype.initPlupload = function () {
		var nettePlupload = this;
		nettePlupload.$dropZone.find('.np-drop-zone-text').text(plupload.translate('Drag files here.'));
		var options = {
			autostart: true,
			runtimes: 'html5,flash,silverlight',
			url: this.options.url,
			multi_selection: true,
			drop_element: nettePlupload.$dropZone.prop('id'),
			browse_button: nettePlupload.$element.find('.np-add-file').prop('id'),
			init: {
				FilesAdded: function (uploader, files) {
					this.isUploaded = false;
					uploader.start();
					nettePlupload.addFiles(files);
				},

				UploadProgress: function (uploader, file) {
					var fileInQueue = nettePlupload.getFileInQueueById(file.id);
					if (fileInQueue) {
						fileInQueue.reportProgress(file.percent);
					}
				},
				FileUploaded: function (uploader, file) {
					var fileInQueue = nettePlupload.getFileInQueueById(file.id);
					if (fileInQueue) {
						fileInQueue.reportProgress(file.percent, true);
					}
				},
				UploadComplete: function (uploader, file) {
					nettePlupload.isUploaded = true;
					if (nettePlupload.onUploadComplete) {
						nettePlupload.onUploadComplete(this);
					}
				},
				Error: function (uploader, error) {
					var fileInQueue = nettePlupload.getFileInQueueById(error.file.id);
					if (fileInQueue) {
						fileInQueue.reportProgress(-1);
					} else {
						alert(error.message);
					}
				}
			}
		};

		$.extend(options, this.options);
		this.uploader = new plupload.Uploader(options);
		this.uploader.init();
	};

	var FileInQueue = function (data, nettePlupload) {
		this.data = data;
		this.nettePlupload = nettePlupload;
		this.addToDropZone();
	};

	FileInQueue.prototype.addToDropZone = function () {
		var $container = $(
			'<div class="np-file-in-queue">' +
			'<div class="name">' + this.data.name + '</div>' +
			'<div class="progress-bar"><div class="number">0%</div><div class="progress">&nbsp;</div></div>' +
			'<div class="status-text">' + plupload.translate('In queue...') + '</div>' +
			'<div class="remove-button"><a href="#">&nbsp;</a></div>' +
			'</div>'
		);

		this.nettePlupload.$dropZone.find('.np-drop-zone-text').addClass('collapsed');
		this.nettePlupload.$dropZone.find('.np-drop-zone-files').append($container);
		this.$container = $container;
	};

	FileInQueue.prototype.reportProgress = function (percent, isComplete) {
		var $statusTextContainer = this.$container.find('.status-text');
		if (percent < 0) {
			$statusTextContainer.text(plupload.translate('An error occurred'));
			percent = 100;
		} else if (isComplete) {
			$statusTextContainer.text(plupload.translate('Finished'));
		} else if (percent > 0) {
			$statusTextContainer.text(plupload.translate('Uploading...'));
		}

		var percentString = percent + '%';
		this.$container.find('.number').text(percentString);
		this.$container.find('.progress').animate({
			'width': percentString
		}, 100);
	};

	$('.nette-plupload').each(function () {
		new NettePlupload(this);
	});
})(jQuery);