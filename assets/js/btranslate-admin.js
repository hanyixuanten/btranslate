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

	function format(template, values) {
		return template.replace(/%([1-9]\d*)\$s/g, function (match, position) {
			return values[Number(position) - 1];
		});
	}

	function renderProgress(progress) {
		var summary = document.createElement('p');
		var percent = document.createElement('strong');
		percent.textContent = progress.percent + '%';
		summary.appendChild(percent);
		appendText(summary, ' ' + format(btranslateAdmin.i18n.contentItems, [progress.completed, progress.total]));

		var progressBar = document.createElement('div');
		progressBar.style.cssText = 'max-width:480px;height:12px;background:#dcdcde';
		var progressValue = document.createElement('div');
		progressValue.style.cssText = 'height:12px;background:#2271b1';
		progressValue.style.width = progress.percent + '%';
		progressBar.appendChild(progressValue);

		var description = document.createElement('p');
		description.className = 'description';
		description.textContent = format(btranslateAdmin.i18n.latestTask, [progress.task_label, progress.posts_completed, progress.posts_total, progress.terms_completed, progress.terms_total]);

		var children = [summary, progressBar, description];
		if (progress.failed_items.length) {
			var failedHeading = document.createElement('h3');
			failedHeading.textContent = btranslateAdmin.i18n.failedItems;
			children.push(failedHeading);

			var failedList = document.createElement('ul');
			progress.failed_items.forEach(function (item) {
				var failedItem = document.createElement('li');
				appendText(failedItem, item.name + ' (' + item.language + ') ');

				var retryLink = document.createElement('a');
				retryLink.className = 'button button-small';
				retryLink.href = item.retry_url;
				retryLink.textContent = btranslateAdmin.i18n.retranslate;
				failedItem.appendChild(retryLink);
				failedList.appendChild(failedItem);
			});
			children.push(failedList);
		}

		container.replaceChildren.apply(container, children);
	}

	function renderProgressError() {
		var message = document.createElement('p');
		message.textContent = btranslateAdmin.i18n.progressError;
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
