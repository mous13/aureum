import "../styles/frontend.css";
import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    connect()
    {
        console.log('Aureum connected')
    }
}