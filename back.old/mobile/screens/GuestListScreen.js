import React, { useState, useEffect } from 'react';
import {
  View,
  Text,
  FlatList,
  TextInput,
  StyleSheet,
  TouchableOpacity,
} from 'react-native';
import api from '../services/api';

export default function GuestListScreen({ route }) {
  const { eventId } = route.params;
  const [guests, setGuests] = useState([]);
  const [search, setSearch] = useState('');

  useEffect(() => {
    loadGuests();
  }, []);

  const loadGuests = async () => {
    try {
      const response = await api.get('/registrations', {
        params: { event_id: eventId },
      });
      setGuests(response.data.registrations || []);
    } catch (error) {
      console.error('Error loading guests:', error);
    }
  };

  const filteredGuests = guests.filter(
    (guest) =>
      guest.first_name.toLowerCase().includes(search.toLowerCase()) ||
      guest.last_name.toLowerCase().includes(search.toLowerCase()) ||
      guest.email.toLowerCase().includes(search.toLowerCase())
  );

  return (
    <View style={styles.container}>
      <TextInput
        style={styles.search}
        placeholder="Rechercher un invité..."
        value={search}
        onChangeText={setSearch}
      />
      <FlatList
        data={filteredGuests}
        keyExtractor={(item) => item.id.toString()}
        renderItem={({ item }) => (
          <View style={styles.guestCard}>
            <Text style={styles.guestName}>
              {item.first_name} {item.last_name}
            </Text>
            <Text style={styles.guestEmail}>{item.email}</Text>
            <Text style={styles.guestStatus}>Statut: {item.status}</Text>
          </View>
        )}
      />
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#f5f5f5',
  },
  search: {
    backgroundColor: '#fff',
    padding: 15,
    margin: 10,
    borderRadius: 8,
    borderWidth: 1,
    borderColor: '#ddd',
  },
  guestCard: {
    backgroundColor: '#fff',
    padding: 15,
    margin: 10,
    borderRadius: 8,
    borderWidth: 1,
    borderColor: '#ddd',
  },
  guestName: {
    fontSize: 16,
    fontWeight: 'bold',
    marginBottom: 5,
  },
  guestEmail: {
    fontSize: 14,
    color: '#666',
    marginBottom: 5,
  },
  guestStatus: {
    fontSize: 12,
    color: '#0ea5e9',
  },
});

