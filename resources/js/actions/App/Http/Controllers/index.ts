import BlogController from './BlogController'
import Settings from './Settings'

const Controllers = {
    BlogController: Object.assign(BlogController, BlogController),
    Settings: Object.assign(Settings, Settings),
}

export default Controllers