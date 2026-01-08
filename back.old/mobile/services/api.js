import axios from 'axios';

// TODO: Remplacer par votre URL API
const API_URL = 'https://your-domain.com/api';

const api = axios.create({
  baseURL: API_URL,
  headers: {
    'Content-Type': 'application/json',
  },
  timeout: 10000,
});

export default api;

