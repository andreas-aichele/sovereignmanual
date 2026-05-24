import HomeController from './HomeController'
import BlogController from './BlogController'
import Settings from './Settings'

const Controllers = {
    HomeController: Object.assign(HomeController, HomeController),
    BlogController: Object.assign(BlogController, BlogController),
    Settings: Object.assign(Settings, Settings),
}

export default Controllers