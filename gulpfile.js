const {src, dest, parallel, series, watch} = require('gulp');
const babelify = require('babelify');
const browserify = require('gulp-bro');
const sass = require('gulp-sass')(require('sass'));
const concat = require('gulp-concat');
const maps = require('gulp-sourcemaps');
const minify = require('gulp-minify');
const uglify = require('gulp-uglify');
const rename = require('gulp-rename');

function _css() {
    return src('assets/css/**/*.scss')
        .pipe(sass())
        .pipe(minify())
        .pipe(rename('style.min.css'))
        .pipe(dest('./dist/'));
}

function _js() {
    return src('./assets/js/**/*.js')
        .pipe(maps.init())
        .pipe(browserify({transform: [babelify.configure({presets: ['@babel/preset-env']})]}))
        .pipe(concat('scripts.min.js'))
        .pipe(uglify())
        .pipe(maps.write('/', {}))
        .pipe(dest('./dist/'));
}

function _watch() {
    watch('./assets/css/**/*.scss', _css);
    watch('./assets/js/**/*.js', _js);
}

exports.css = _css;
exports.js = _js;
exports.watch = _watch;
exports.build = parallel(_css, _js);
exports.default = series(parallel(_css, _js), _watch);
