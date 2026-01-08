import React, { useState, useEffect } from 'react';
import {
  View,
  Text,
  StyleSheet,
  TouchableOpacity,
  Alert,
} from 'react-native';
import { Camera } from 'expo-camera';
import { BarCodeScanner } from 'expo-barcode-scanner';
import api from '../services/api';

export default function CheckInScreen({ route, navigation }) {
  const { eventId } = route.params;
  const [hasPermission, setHasPermission] = useState(null);
  const [scanned, setScanned] = useState(false);

  useEffect(() => {
    (async () => {
      const { status } = await Camera.requestCameraPermissionsAsync();
      setHasPermission(status === 'granted');
    })();
  }, []);

  const handleBarCodeScanned = async ({ type, data }) => {
    if (scanned) return;
    setScanned(true);

    try {
      const response = await api.post('/checkin/scan', {
        qr_data: data,
        event_id: eventId,
      });

      if (response.data.success) {
        Alert.alert(
          'Check-in réussi',
          `${response.data.registration.first_name} ${response.data.registration.last_name} a été enregistré`,
          [
            {
              text: 'OK',
              onPress: () => {
                setScanned(false);
                navigation.navigate('GuestList', { eventId });
              },
            },
          ]
        );
      }
    } catch (error) {
      Alert.alert('Erreur', error.response?.data?.error || 'Erreur lors du check-in');
      setScanned(false);
    }
  };

  if (hasPermission === null) {
    return <View style={styles.center}><Text>Demande d'autorisation...</Text></View>;
  }
  if (hasPermission === false) {
    return <View style={styles.center}><Text>Accès à la caméra refusé</Text></View>;
  }

  return (
    <View style={styles.container}>
      <BarCodeScanner
        onBarCodeScanned={scanned ? undefined : handleBarCodeScanned}
        style={StyleSheet.absoluteFillObject}
      />
      <View style={styles.overlay}>
        <View style={styles.scanArea} />
        <Text style={styles.instruction}>Scannez le QR code de l'invité</Text>
      </View>
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
  },
  center: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
  },
  overlay: {
    flex: 1,
    backgroundColor: 'rgba(0,0,0,0.5)',
    justifyContent: 'center',
    alignItems: 'center',
  },
  scanArea: {
    width: 250,
    height: 250,
    borderWidth: 2,
    borderColor: '#0ea5e9',
    borderRadius: 10,
  },
  instruction: {
    color: '#fff',
    fontSize: 16,
    marginTop: 20,
    textAlign: 'center',
  },
});

