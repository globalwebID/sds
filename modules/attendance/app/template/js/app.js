//'use strict';

$(window).on('load', function () {
    var body = $('body');
    switch ($('body').attr('data-page')) {
        case "homepage":
            /* carousel */
            var swiper = new Swiper('.introduction', {
                autoplay: true,
                pagination: {
                    el: '.swiper-pagination',
                },
            });
            
            var swiper = new Swiper('.swiper-users', {
                slidesPerView: 'auto',
                spaceBetween: 15,
                pagination: 'false'
            });

            var swiper = new Swiper('.addsendcarousel', {
                slidesPerView: '3',
                spaceBetween: 15,
                pagination: {
                    el: '.swiper-pagination',
                },
            });


            var swiper = new Swiper('.swiper-home-count', {
                slidesPerView: 2,
                spaceBetween: 15,
                pagination: false
               
            });

            var swiper = new Swiper('.swiper-home-article', {
                slidesPerView: 2,
                spaceBetween: 15,
                breakpoints: {
                    480: {
                        slidesPerView: 1,
                        spaceBetween: 15,
                    },
                    640: {
                        slidesPerView: 3,
                        spaceBetween:15,
                    },
                    1024: {
                        slidesPerView: 3,
                        spaceBetween: 15,
                    },
                    1440: {
                        slidesPerView: 3,
                        spaceBetween: 15,
                    },
                    1920: {
                        slidesPerView: 4,
                        spaceBetween: 15,
                    }
                },
                pagination: {
                    el: '.swiper-pagination',
                },
            });


            $('#more-expand-btn').on('click', function () {
                $('#more-expand').addClass("active");
                $(this).addClass("active");
            });

            /* carousel */
            var swiper3 = new Swiper('.categoriestab1', {
                slidesPerView: 'auto',
                spaceBetween: 15,
            });
            var swiper4 = new Swiper('.categories2tab1', {
                slidesPerView: 'auto',
                spaceBetween: 10,
            });

          
            break;
        case "landing":
            /* carousel */
            var swiper = new Swiper('.introduction', {
                autoplay: true,
                pagination: {
                    el: '.swiper-pagination',
                },
            });

            break;
        case "tooltips":
            $(function () {
                $('[data-toggle="tooltip"]').tooltip()
            });
            
            break;
    }
});