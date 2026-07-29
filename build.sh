#!/usr/bin/env sh

set -eu

plugin_slug="hyx-translator-for-baidu-translate"
plugin_file="${plugin_slug}.php"
script_dir="$(CDPATH='' cd -- "$(dirname -- "$0")" && pwd)"
build_dir="${script_dir}/build"
plugin_dir="${build_dir}/${plugin_slug}"

clean() {
	find "${script_dir}" -maxdepth 1 -type f -name "${plugin_slug}-*.zip" -delete
	rm -rf "${build_dir}"
}

build() {
	for command in grep sed msgfmt zip unzip; do
		if ! command -v "${command}" >/dev/null 2>&1; then
			printf 'Required command not found: %s\n' "${command}" >&2
			exit 1
		fi
	done

	version="$(grep -E '^ \* Version:' "${script_dir}/${plugin_file}" | sed -E 's/^ \* Version: *//')"
	archive_name="${plugin_slug}-${version}.zip"
	archive_path="${script_dir}/${archive_name}"

	clean
	mkdir -p "${plugin_dir}"
	cp "${script_dir}/${plugin_file}" \
		"${script_dir}/uninstall.php" \
		"${script_dir}/readme.txt" \
		"${script_dir}/readme-zh_CN.txt" \
		"${script_dir}/LICENSE" \
		"${plugin_dir}/"
	cp -R "${script_dir}/includes" "${plugin_dir}/"
	cp -R "${script_dir}/assets" "${plugin_dir}/"
	cp -R "${script_dir}/languages" "${plugin_dir}/"
	msgfmt --check --check-format \
		--output-file="${plugin_dir}/languages/${plugin_slug}-zh_CN.mo" \
		"${script_dir}/languages/${plugin_slug}-zh_CN.po"
	(
		cd "${build_dir}"
		zip -qr "${archive_path}" "${plugin_slug}"
	)

	unzip -p "${archive_path}" "${plugin_slug}/${plugin_file}" | grep -q '^ \* Plugin Name: HTBD$'
	unzip -p "${archive_path}" "${plugin_slug}/readme.txt" | grep -q "^Stable tag: ${version}$"
	unzip -p "${archive_path}" "${plugin_slug}/readme-zh_CN.txt" | grep -q "^Stable tag: ${version}$"
	unzip -Z1 "${archive_path}" | grep -q "^${plugin_slug}/languages/${plugin_slug}-zh_CN.mo$"
	unzip -Z1 "${archive_path}" | grep -q "^${plugin_slug}/includes/interface-translation-provider.php$"
	unzip -Z1 "${archive_path}" | grep -q "^${plugin_slug}/assets/js/admin.js$"
	if unzip -Z1 "${archive_path}" | grep -Eq '/class-btranslate-[^/]+\.php$'; then
		printf 'Unexpected legacy class filename found in archive.\n' >&2
		exit 1
	fi
	if unzip -Z1 "${archive_path}" | grep -Eq '/(interface-btranslate-translation-provider\.php|btranslate-admin\.js)$'; then
		printf 'Unexpected legacy runtime filename found in archive.\n' >&2
		exit 1
	fi
	if grep -R -q 'BTRANSLATE' "${plugin_dir}" --include='*.php'; then
		printf 'Unexpected legacy PHP prefix found in archive.\n' >&2
		exit 1
	fi
	if unzip -Z1 "${archive_path}" | grep -Eq '/README(\.zh-CN)?\.md$'; then
		printf 'Unexpected development README found in archive.\n' >&2
		exit 1
	fi

	printf 'Built %s\n' "${archive_path}"
}

case "${1:-build}" in
	build)
		build
		;;
	clean)
		clean
		printf 'Removed local build artifacts.\n'
		;;
	*)
		printf 'Usage: %s [build|clean]\n' "$0" >&2
		exit 2
		;;
esac