import { useParams } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import api from '../services/api';

export default function EventDetail() {
  const { id } = useParams();
  
  const { data, isLoading } = useQuery({
    queryKey: ['event', id],
    queryFn: async () => {
      const response = await api.get(`/events/${id}`);
      return response.data;
    },
  });

  if (isLoading) {
    return <div>Chargement...</div>;
  }

  const event = data?.event;
  const stats = data?.statistics;

  return (
    <div>
      <h1 className="text-3xl font-bold mb-6">{event?.name}</h1>
      <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div className="card">
          <h3 className="font-semibold mb-4">Statistiques</h3>
          <div className="space-y-2">
            <div className="flex justify-between">
              <span className="text-gray-600">Inscriptions</span>
              <span className="font-semibold">{stats?.total_registrations || 0}</span>
            </div>
            <div className="flex justify-between">
              <span className="text-gray-600">Check-in</span>
              <span className="font-semibold">{stats?.checked_in || 0}</span>
            </div>
            <div className="flex justify-between">
              <span className="text-gray-600">Annulés</span>
              <span className="font-semibold">{stats?.cancelled || 0}</span>
            </div>
          </div>
        </div>
        <div className="card md:col-span-2">
          <h3 className="font-semibold mb-4">Détails de l'événement</h3>
          <p className="text-gray-600">{event?.description || 'Aucune description'}</p>
        </div>
      </div>
    </div>
  );
}

