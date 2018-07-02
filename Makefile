styles:
	sass -t compressed --sourcemap=auto --unix-newlines assets/scss/sse.scss > assets/css-compiled/sse.min.css

watch:
	sass -t compressed --watch assets/scss/sse.scss:assets/css-compiled/sse.min.css
