(function () {
	'use strict';

	var domainMode = document.querySelector('#btranslate-routing-mode input[value="domain"]');
	var bindingsRow = document.getElementById('btranslate-domain-bindings-row');

	if (domainMode && bindingsRow) {
		domainMode.addEventListener('change', function () {
			bindingsRow.style.display = domainMode.checked ? '' : 'none';
		});
	}

	document.querySelectorAll('form[data-btranslate-confirm]').forEach(function (form) {
		form.addEventListener('submit', function (event) {
			if (!window.confirm(form.getAttribute('data-btranslate-confirm'))) {
				event.preventDefault();
			}
		});
	});

	var container = document.getElementById('btranslate-translation-progress');
	if (!container || typeof btranslateAdmin === 'undefined') {
		return;
	}

	function appendText(parent, text) {
		parent.appendChild(document.createTextNode(text));
	}

	function renderProgress(progress) {
		var summary = document.createElement('p');
		var percent = document.createElement('strong');
		percent.textContent = progress.percent + '%';
		summary.appendChild(percent);
		appendText(summary, '（' + progress.completed + ' / ' + progress.total + ' 个内容项）');

		var progressBar = document.createElement('div');
		progressBar.style.cssText = 'max-width:480px;height:12px;background:#dcdcde';
		var progressValue = document.createElement('div');
		progressValue.style.cssText = 'height:12px;background:#2271b1';
		progressValue.style.width = progress.percent + '%';
		progressBar.appendChild(progressValue);

		var description = document.createElement('p');
		description.className = 'description';
		description.textContent = '最近任务：' + progress.task_label + '。文章和页面：' + progress.posts_completed + ' / ' + progress.posts_total + '；分类和标签：' + progress.terms_completed + ' / ' + progress.terms_total + '。每 5 秒自动更新。';

		container.replaceChildren(summary, progressBar, description);
	}

	function renderProgressError() {
		var message = document.createElement('p');
		message.textContent = '暂时无法读取翻译进度。';
		container.replaceChildren(message);
	}

	function updateProgress() {
		var url = btranslateAdmin.progressUrl + '?action=btranslate_translation_progress&_ajax_nonce=' + encodeURIComponent(btranslateAdmin.progressNonce);

		window.fetch(url, { credentials: 'same-origin' })
			.then(function (response) {
				if (!response.ok) {
					throw new Error('progress_request_failed');
				}
				return response.json();
			})
			.then(function (response) {
				if (!response.success) {
					throw new Error('progress_request_failed');
				}
				renderProgress(response.data);
			})
			.catch(renderProgressError);
	}

	updateProgress();
	window.setInterval(updateProgress, 5000);
}());
