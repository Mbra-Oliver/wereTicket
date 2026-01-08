import { useQuery } from '@tanstack/react-query';
import { Link } from 'react-router-dom';
import api from '../services/api';
import { Plus, Calendar, MapPin } from 'lucide-react';
import { format } from 'date-fns';
import { fr } from 'date-fns/locale';

export default function Events() {
  const { data, isLoading, refetch } = useQuery({
    queryKey: ['events'],
    queryFn: async () => {
      const response = await api.get('/events');
      return response.data;
    },
  });

  if (isLoading) {
    return <div className="text-center py-12">Chargement...</div>;
  }

  const events = data?.events || [];

  return (
    <div>
      <div className="flex items-center justify-between mb-8">
        <div>
          <h1 className="text-3xl font-bold text-gray-900">Événements</h1>
          <p className="text-gray-600 mt-2">Gérez tous vos événements</p>
        </div>
        <Link
          to="/events/new"
          className="btn btn-primary flex items-center gap-2"
        >
          <Plus size={20} />
          Nouvel événement
        </Link>
      </div>

      {events.length === 0 ? (
        <div className="card text-center py-12">
          <Calendar className="mx-auto text-gray-400 mb-4" size={48} />
          <h3 className="text-lg font-semibold text-gray-900 mb-2">
            Aucun événement
          </h3>
          <p className="text-gray-600 mb-6">
            Créez votre premier événement pour commencer
          </p>
          <Link to="/events/new" className="btn btn-primary">
            Créer un événement
          </Link>
        </div>
      ) : (
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          {events.map((event) => (
            <Link
              key={event.id}
              to={`/events/${event.id}`}
              className="card hover:shadow-lg transition-shadow"
            >
              <div className="flex items-start justify-between mb-4">
                <div>
                  <h3 className="text-lg font-semibold text-gray-900 mb-1">
                    {event.name}
                  </h3>
                  <p className="text-sm text-gray-500">{event.event_type}</p>
                </div>
                <span
                  className={`px-2 py-1 rounded text-xs font-medium ${
                    event.status === 'published'
                      ? 'bg-green-100 text-green-800'
                      : event.status === 'draft'
                      ? 'bg-gray-100 text-gray-800'
                      : 'bg-blue-100 text-blue-800'
                  }`}
                >
                  {event.status}
                </span>
              </div>

              <div className="space-y-2 text-sm text-gray-600">
                <div className="flex items-center gap-2">
                  <Calendar size={16} />
                  <span>
                    {format(new Date(event.start_date), 'd MMM yyyy', {
                      locale: fr,
                    })}
                  </span>
                </div>
                <div className="flex items-center gap-2">
                  <MapPin size={16} />
                  <span>{event.format}</span>
                </div>
              </div>

              <div className="mt-4 pt-4 border-t border-gray-200 flex items-center justify-between text-sm">
                <span className="text-gray-600">
                  {event.registration_count || 0} inscriptions
                </span>
                <span className="text-primary-600 font-medium">
                  Voir détails →
                </span>
              </div>
            </Link>
          ))}
        </div>
      )}
    </div>
  );
}

