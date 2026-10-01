import { buildRefs, getJSON } from '@/assets/scripts/helpers.js'
import Swiper from 'swiper'
import { Autoplay, A11y, Controller, Navigation } from 'swiper/modules'
import 'swiper/css'
import 'swiper/css/autoplay'
import 'swiper/css/a11y'
import 'swiper/css/controller'
import 'swiper/css/navigation'

export default function (el) {
  const refs = buildRefs(el)
  const data = getJSON(el)

  const swipers = initSliders(refs, data)
  return () => swipers.forEach(swiper => swiper.destroy())
}

function initSliders (refs, data) {
  const { options } = data

  const swiperSliderThumb = new Swiper(refs.sliderThumb, {
    modules: [Autoplay, A11y, Controller, Navigation],
    a11y: options.a11y,
    roundLengths: true,
    spaceBetween: 10,
    slidesPerView: 'auto',
    freeMode: true,
    centeredSlides: true,
    slideToClickedSlide: true,
    lazy: {
      loadPrevNext: true,
      loadPrevNextAmount: 10
    }
  })

  const swiperSliderMain = new Swiper(refs.sliderMain, {
    modules: [Navigation, A11y, Controller, Autoplay],
    roundLengths: true,
    navigation: {
      nextEl: refs.next,
      prevEl: refs.prev
    },
    a11y: options.a11y,
    controller: {
      control: swiperSliderThumb
    }
  })

  swiperSliderThumb.controller.control = swiperSliderMain

  return [swiperSliderMain, swiperSliderThumb]
}
