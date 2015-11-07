/* jshint node:true */
/*!
 * gulp
 * $ npm install gulp-sass gulp-autoprefixer gulp-minify-css gulp-jshint gulp-concat gulp-uglify gulp-imagemin gulp-rename gulp-livereload gulp-cache del gulp-sourcemaps --save-dev
 */

// Load plugins
var gulp = require('gulp'),
    sass = require('gulp-sass'),
    sourcemaps = require('gulp-sourcemaps'),
    autoprefixer = require('gulp-autoprefixer'),
    minifycss = require('gulp-minify-css'),
    jshint = require('gulp-jshint'),
    uglify = require('gulp-uglify'),
    imagemin = require('gulp-imagemin'),
    rename = require('gulp-rename'),
    concat = require('gulp-concat'),
    cache = require('gulp-cache'),
    livereload = require('gulp-livereload'),
    del = require('del');

// Styles
gulp.task('styles', function() {
    return gulp.src('sass/*.scss')
        .pipe(sourcemaps.init())
        .pipe(sass())
        .pipe(autoprefixer('last 2 version'))
        .pipe(sourcemaps.write())
        .pipe(gulp.dest('dist/styles'))
        .pipe(rename({ suffix: '.min' }))
        .pipe(minifycss())
        .pipe(gulp.dest('dist/styles'));
});



// Scripts
gulp.task('scripts', function() {
    return gulp.src('js/*.js')
        .pipe(jshint('.jshintrc'))
        .pipe(jshint.reporter('default'))
        .pipe(concat('main.js'))
        .pipe(gulp.dest('dist/scripts'))
        .pipe(rename({ suffix: '.min' }))
        .pipe(uglify())
        .pipe(gulp.dest('dist/scripts'));
});

// Libraries - scripts
gulp.task('libs-scripts', function() {
    return gulp.src('libs/*.js')
        .pipe(concat('libs.js'))
        .pipe(gulp.dest('dist/scripts'));
});

gulp.task('libs-styles', function() {
    return gulp.src('libs/*.css')
        .pipe(concat('libs.css'))
        .pipe(rename({ suffix: '.min' }))
        .pipe(minifycss())
        .pipe(gulp.dest('dist/styles'))
});

// Images
gulp.task('images', function() {
    return gulp.src('images/*')
        .pipe(cache(imagemin({ optimizationLevel: 3, progressive: true, interlaced: true })))
        .pipe(gulp.dest('dist/images'));
});

// Clean
gulp.task('clean', function(cb) {
    del(['dist/styles', 'dist/scripts', 'dist/images'], cb);
});

// Default task
gulp.task('default', ['clean'], function() {
    gulp.start('styles', 'scripts', 'images', 'libs-styles', 'libs-scripts');
});

// Watch
gulp.task('watch', function() {

    // Watch .scss files
    gulp.watch('sass/*.scss', ['styles']);

    // Watch .js files
    gulp.watch('js/*.js', ['scripts']);

    // Watch image files
    gulp.watch('images/*', ['images']);

    // Create LiveReload server
    livereload.listen();

    // Watch any files in dist/, reload on change
    gulp.watch(['dist/**']).on('change', livereload.changed);

});
