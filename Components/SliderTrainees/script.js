import { buildRefs, getJSON } from '@/assets/scripts/helpers.js'
import Swiper from 'swiper'
import { Autoplay, A11y, Navigation } from 'swiper/modules'
import 'swiper/css'
import 'swiper/css/autoplay'
import 'swiper/css/a11y'
import 'swiper/css/navigation'

export default function (el) {
  const refs = buildRefs(el)
  const data = getJSON(el)

  const swiper = initSlider(refs, data)

  return () => swiper.destroy()
}

function initSlider (refs, data) {
  const { options } = data
  const config = {
    modules: [Autoplay, A11y, Navigation],
    a11y: options.a11y,
    roundLengths: true,
    slideToClickedSlide: true,
    slidesPerView: 1,
    spaceBetween: 20,
    breakpoints: {
      // when window width is >= 375px
      375: {
        slidesPerView: 1.2,
        spaceBetween: 0,
      },
      // when window width is >= 640px
      640: {
        slidesPerView: 2,
        spaceBetween: 0,
      },
      // when window width is >= 991px
      991: {
        slidesPerView: 4,
        spaceBetween: 0,
      }
    },
    navigation: {
      nextEl: refs.next,
      prevEl: refs.prev
    }
  }
  if (options.autoplay && options.autoplaySpeed) {
    config.autoplay = {
      delay: options.autoplaySpeed
    }
  }

  return new Swiper(refs.slider, config)
}
