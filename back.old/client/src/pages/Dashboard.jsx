import { useQuery } from '@tanstack/react-query';
import api from '../services/api';
import { Calendar, Users, CheckSquare, TrendingUp } from 'lucide-react';

export default function Dashboard() {
  const { data: stats, isLoading } = useQuery({
    queryKey: ['dashboard-stats'],
    queryFn: async () => {
      const response = await api.get('/statistics/dashboard');
      return response.data;
    },
  });

  if (isLoading) {
    return <div className="text-center py-12">Chargement...</div>;
  }

  const cards = [
    {
      title: 'Événements',
      value: stats?.events?.total_events || 0,
      subtitle: `${stats?.events?.active_events || 0} actifs`,
      icon: Calendar,
      color: 'bg-blue-500',
    },
    {
      title: 'Inscriptions',
      value: stats?.registrations?.total_registrations || 0,
      subtitle: `${stats?.registrations?.checked_in || 0} check-in`,
      icon: Users,
      color: 'bg-green-500',
    },
    {
      title: 'Contacts',
      value: stats?.contacts?.total_contacts || 0,
      subtitle: 'Base de données',
      icon: Users,
      color: 'bg-purple-500',
    },
    {
      title: 'Taux de présence',
      value: stats?.registrations?.total_registrations
        ? Math.round((stats?.registrations?.checked_in / stats?.registrations?.total_registrations) * 100)
        : 0,
      subtitle: '%',
      icon: TrendingUp,
      color: 'bg-orange-500',
    },
  ];

  return (
    <div>
      <div className="mb-8">
        <h1 className="text-3xl font-bold text-gray-900">Dashboard</h1>
        <p className="text-gray-600 mt-2">Vue d'ensemble de vos événements</p>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        {cards.map((card, index) => {
          const Icon = card.icon;
          return (
            <div key={index} className="card">
              <div className="flex items-center justify-between">
                <div>
                  <p className="text-sm text-gray-600 mb-1">{card.title}</p>
                  <p className="text-3xl font-bold text-gray-900">{card.value}</p>
                  <p className="text-sm text-gray-500 mt-1">{card.subtitle}</p>
                </div>
                <div className={`${card.color} p-3 rounded-lg`}>
                  <Icon className="text-white" size={24} />
                </div>
              </div>
            </div>
          );
        })}
      </div>

      <div className="card">
        <h2 className="text-xl font-semibold mb-4">Événements récents</h2>
        <p className="text-gray-500">Aucun événement récent</p>
      </div>
    </div>
  );
}

