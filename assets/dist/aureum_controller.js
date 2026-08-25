import "../styles/scaling.css";
import "../styles/frontend.css";
import "../styles/dashboard.css";
import "../styles/events.css";
import "../styles/rooms.css";
import "../styles/form-panel.css";
import "../styles/bookings.css";
import "../styles/docs.css";
import "../styles/amenities.css";
import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    connect()
    {
        console.log('Aureum connected')
    }
}