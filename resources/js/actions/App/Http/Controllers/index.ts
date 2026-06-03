import MagazineController from './MagazineController'
import Settings from './Settings'

const Controllers = {
    MagazineController: Object.assign(MagazineController, MagazineController),
    Settings: Object.assign(Settings, Settings),
}

export default Controllers